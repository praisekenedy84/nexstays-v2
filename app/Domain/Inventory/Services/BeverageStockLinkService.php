<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Actions\SyncMenuItemRecipe;
use App\Domain\Inventory\Models\RecipeIngredient;
use App\Domain\Inventory\Models\StockItem;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Outlet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BeverageStockLinkService
{
    public function __construct(
        private readonly SyncMenuItemRecipe $syncMenuItemRecipe,
        private readonly InventoryAvailabilityService $inventoryAvailability,
    ) {}

    /**
     * @return Collection<int, MenuItem>
     */
    public function unlinkedBarMenuItems(?string $outletId = null): Collection
    {
        return MenuItem::query()
            ->with('category.outlet')
            ->whereHas('category.outlet', function ($q) use ($outletId) {
                $q->where('type', 'bar');
                if ($outletId !== null) {
                    $q->where('id', $outletId);
                }
            })
            ->whereDoesntHave('recipeIngredients')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, StockItem>
     */
    public function awaitingStockItems(?string $outletId = null): Collection
    {
        return StockItem::query()
            ->with(['outlet', 'menuItem.category'])
            ->where('awaiting_stock', true)
            ->when($outletId !== null, fn ($q) => $q->where('outlet_id', $outletId))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, StockItem>
     */
    public function stockItemsForBarOutlet(string $outletId): Collection
    {
        return StockItem::query()
            ->where(fn ($q) => $q->where('outlet_id', $outletId)->orWhereNull('outlet_id'))
            ->orderBy('name')
            ->get();
    }

    public function link(MenuItem $menuItem, StockItem $stockItem, float $quantity, string $unit): void
    {
        DB::transaction(function () use ($menuItem, $stockItem, $quantity, $unit) {
            $outletId = $menuItem->category->outlet_id;

            $stockItem->update([
                'menu_item_id' => $menuItem->id,
                'outlet_id' => $stockItem->outlet_id ?? $outletId,
                'awaiting_stock' => (float) $stockItem->current_stock <= 0,
            ]);

            $this->syncMenuItemRecipe->execute($menuItem, [[
                'stock_item_id' => $stockItem->id,
                'quantity' => $quantity,
                'unit' => $unit,
            ]]);

            $this->mirrorMenuToLinkedStock($menuItem->fresh(['linkedStockItem']));
            $this->syncMenuAvailability($menuItem->fresh(['recipeIngredients.stockItem']));
        });
    }

    public function createAwaitingStockForMenu(
        MenuItem $menuItem,
        float $quantity = 1,
        string $unit = 'bottle'
    ): StockItem {
        return DB::transaction(function () use ($menuItem, $quantity, $unit) {
            $outlet = $menuItem->category->outlet;

            $existing = StockItem::query()
                ->where('menu_item_id', $menuItem->id)
                ->first();

            if ($existing !== null) {
                $this->link($menuItem, $existing, $quantity, $unit);

                return $existing->fresh();
            }

            $stockItem = StockItem::query()->create([
                'outlet_id' => $outlet->id,
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'category' => 'beverage',
                'unit' => $unit,
                'current_stock' => 0,
                'reorder_level' => 0,
                'awaiting_stock' => true,
                'cost_per_unit' => $menuItem->cost,
            ]);

            $this->syncMenuItemRecipe->execute($menuItem, [[
                'stock_item_id' => $stockItem->id,
                'quantity' => $quantity,
                'unit' => $unit,
            ]]);

            $stockItem = $stockItem->fresh(['menuItem']);
            $this->syncMenuAvailability($menuItem->fresh(['recipeIngredients.stockItem']));

            return $stockItem;
        });
    }

    public function mirrorMenuToLinkedStock(MenuItem $menuItem): void
    {
        $menuItem->loadMissing(['linkedStockItem', 'category']);

        $stock = $menuItem->linkedStockItem;
        if ($stock === null) {
            return;
        }

        $stock->update([
            'name' => $menuItem->name,
            'cost_per_unit' => $menuItem->cost ?? $stock->cost_per_unit,
            'menu_item_id' => $menuItem->id,
            'outlet_id' => $menuItem->category->outlet_id ?? $stock->outlet_id,
        ]);
    }

    public function syncMenuAvailability(MenuItem $menuItem): void
    {
        $menuItem->loadMissing(['category.outlet', 'recipeIngredients.stockItem']);

        if (! $menuItem->category->outlet->tracksBeverageInventory()) {
            return;
        }

        if ($menuItem->recipeIngredients->isEmpty()) {
            return;
        }

        $canServe = $this->canFulfill($menuItem, 1);

        if ($menuItem->is_available !== $canServe) {
            $menuItem->update(['is_available' => $canServe]);
        }
    }

    public function syncStockItem(StockItem $stockItem): void
    {
        $stockItem->loadMissing(['menuItem.category.outlet', 'menuItem.recipeIngredients']);

        if ($stockItem->menu_item_id === null || $stockItem->menuItem === null) {
            return;
        }

        $menuItem = $stockItem->menuItem;

        if (! $menuItem->category->outlet->tracksBeverageInventory()) {
            return;
        }

        $hasRecipe = $menuItem->recipeIngredients->contains(
            fn (RecipeIngredient $ingredient) => $ingredient->stock_item_id === $stockItem->id
        );

        if (! $hasRecipe) {
            $existing = $menuItem->recipeIngredients->first();
            $this->link(
                $menuItem,
                $stockItem,
                (float) ($existing?->quantity ?? 1),
                (string) ($existing?->unit ?? $stockItem->unit)
            );

            return;
        }

        $this->mirrorMenuToLinkedStock($menuItem);
        $this->syncMenuAvailability($menuItem->fresh(['recipeIngredients.stockItem']));
    }

    public function detachStockForMenuItem(MenuItem $menuItem): void
    {
        StockItem::query()
            ->where('menu_item_id', $menuItem->id)
            ->update(['menu_item_id' => null]);
    }

    /**
     * @return array{linked: int, created: int, repaired: int, availability_updated: int}
     */
    public function reconcileBarOutlet(?string $outletId = null): array
    {
        $stats = ['linked' => 0, 'created' => 0, 'repaired' => 0, 'availability_updated' => 0];

        $barMenuItems = MenuItem::query()
            ->with(['category.outlet', 'recipeIngredients', 'linkedStockItem'])
            ->whereHas('category.outlet', function ($q) use ($outletId) {
                $q->where('type', 'bar');
                if ($outletId !== null) {
                    $q->where('id', $outletId);
                }
            })
            ->get();

        foreach ($barMenuItems as $menuItem) {
            if ($menuItem->recipeIngredients->isEmpty()) {
                if ($menuItem->linkedStockItem !== null) {
                    $this->link(
                        $menuItem,
                        $menuItem->linkedStockItem,
                        1,
                        (string) $menuItem->linkedStockItem->unit
                    );
                    $stats['repaired']++;
                } else {
                    $this->createAwaitingStockForMenu($menuItem);
                    $stats['created']++;
                }
            } else {
                $this->mirrorMenuToLinkedStock($menuItem);
                $stats['linked']++;
            }

            $before = $menuItem->is_available;
            $this->syncMenuAvailability($menuItem->fresh(['recipeIngredients.stockItem']));
            if ($menuItem->fresh()->is_available !== $before) {
                $stats['availability_updated']++;
            }
        }

        $orphanStocks = StockItem::query()
            ->with('menuItem')
            ->whereNotNull('menu_item_id')
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->get()
            ->filter(fn (StockItem $stock) => $stock->menuItem === null);

        foreach ($orphanStocks as $stock) {
            $stock->update(['menu_item_id' => null]);
            $stats['repaired']++;
        }

        return $stats;
    }

    public function canFulfill(MenuItem $menuItem, int $quantity): bool
    {
        try {
            $this->inventoryAvailability->assertCanFulfill($menuItem, $quantity);

            return true;
        } catch (\DomainException) {
            return false;
        }
    }

    /**
     * @param  list<array{stock_item_id?: string|null, quantity?: float|string|null, unit?: string|null}>  $recipeLines
     */
    public function syncMenuInventory(
        MenuItem $menuItem,
        ?string $linkedStockItemId,
        bool $registerAwaitingStock,
        float $serveQuantity,
        string $serveUnit,
        array $recipeLines = []
    ): void {
        $menuItem->loadMissing('category.outlet');

        if (! $menuItem->category->outlet->tracksBeverageInventory()) {
            return;
        }

        if ($linkedStockItemId !== null && $linkedStockItemId !== '') {
            $stockItem = StockItem::query()->findOrFail($linkedStockItemId);
            $this->link($menuItem, $stockItem, $serveQuantity, $serveUnit);

            return;
        }

        if ($recipeLines !== []) {
            $hasRecipe = collect($recipeLines)->contains(
                fn ($line) => ! empty($line['stock_item_id']) && (float) ($line['quantity'] ?? 0) > 0
            );

            if ($hasRecipe) {
                $this->syncMenuItemRecipe->execute($menuItem, $recipeLines);
                $this->mirrorMenuToLinkedStock($menuItem->fresh(['linkedStockItem']));
                $this->syncMenuAvailability($menuItem->fresh(['recipeIngredients.stockItem']));

                return;
            }
        }

        if ($registerAwaitingStock) {
            $this->createAwaitingStockForMenu($menuItem, $serveQuantity, $serveUnit);
        }
    }

    public function syncStockMenuLink(
        StockItem $stockItem,
        ?string $menuItemId,
        float $serveQuantity,
        string $serveUnit
    ): void {
        if ($menuItemId === null || $menuItemId === '') {
            return;
        }

        $menuItem = MenuItem::query()
            ->with('category.outlet')
            ->findOrFail($menuItemId);

        throw_if(
            ! $menuItem->category->outlet->tracksBeverageInventory(),
            \DomainException::class,
            'Only bar menu items can be linked to beverage stock.'
        );

        $this->link($menuItem, $stockItem, $serveQuantity, $serveUnit);
    }

    public function clearAwaitingWhenStocked(StockItem $stockItem): void
    {
        if ((float) $stockItem->current_stock > 0 && $stockItem->awaiting_stock) {
            $stockItem->update(['awaiting_stock' => false]);
        }

        if ($stockItem->menu_item_id !== null) {
            $this->syncStockItem($stockItem->fresh(['menuItem.category.outlet']));
        }
    }

    public function barOutlets(): Collection
    {
        return Outlet::query()
            ->where('type', 'bar')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
