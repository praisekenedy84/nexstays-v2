<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryDeductionService
{
    public function __construct(
        private readonly BeverageStockLinkService $beverageStockLink
    ) {}

    public function deductForOrder(Order $order): void
    {
        $alreadyDeducted = StockMovement::query()
            ->where('reference_id', $order->id)
            ->where('reference_type', Order::class)
            ->where('movement_type', 'consumption')
            ->exists();

        if ($alreadyDeducted) {
            return;
        }

        $menuItemsToSync = [];

        DB::transaction(function () use ($order, &$menuItemsToSync) {
            $order->load(['items.menuItem.recipeIngredients.stockItem', 'waiter']);

            foreach ($order->items as $item) {
                if ($item->status === 'voided') {
                    continue;
                }

                $menuItem = $item->menuItem;
                if ($menuItem === null || ! $menuItem->tracksBeverageInventory()) {
                    continue;
                }

                foreach ($menuItem->recipeIngredients as $ingredient) {
                    $qty = (float) $ingredient->quantity * (int) $item->quantity;
                    if ($qty <= 0) {
                        continue;
                    }

                    $stockItem = $ingredient->stockItem;
                    if ($stockItem === null) {
                        continue;
                    }

                    $before = (float) $stockItem->current_stock;
                    $after = $before - $qty;

                    StockMovement::query()->create([
                        'stock_item_id' => $stockItem->id,
                        'movement_type' => 'consumption',
                        'quantity' => -$qty,
                        'reference_id' => $order->id,
                        'reference_type' => Order::class,
                        'notes' => "Order {$order->order_number} — {$menuItem->name}",
                        'performed_by' => $order->waiter_id,
                    ]);

                    if ($after < 0) {
                        StockMovement::query()->create([
                            'stock_item_id' => $stockItem->id,
                            'movement_type' => 'stock_shortage',
                            'quantity' => $after,
                            'reference_id' => $order->id,
                            'reference_type' => Order::class,
                            'notes' => "Shortage after order {$order->order_number}: required {$qty}, had {$before}",
                            'performed_by' => $order->waiter_id,
                        ]);

                        Log::warning('Stock shortage on order close', [
                            'stock_item_id' => $stockItem->id,
                            'order_id' => $order->id,
                            'required' => $qty,
                            'available' => $before,
                        ]);

                        $after = 0;
                    }

                    $stockItem->update(['current_stock' => $after]);

                    $menuItemsToSync[$menuItem->id] = $menuItem->id;
                }
            }
        });

        if ($menuItemsToSync !== []) {
            MenuItem::query()
                ->with(['category.outlet', 'recipeIngredients.stockItem'])
                ->whereIn('id', array_values($menuItemsToSync))
                ->each(fn (MenuItem $menuItem) => $this->beverageStockLink->syncMenuAvailability($menuItem));
        }
    }
}
