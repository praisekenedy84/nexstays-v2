<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Inventory\Services\BeverageStockLinkService;
use App\Domain\Shared\Models\MenuCategory;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreMenuItemRequest;
use App\Http\Requests\Web\UpdateMenuItemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function __construct(
        private readonly BeverageStockLinkService $beverageStockLink
    ) {}

    public function json(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-menu'), 403);

        $outletId = $request->query('outlet_id');
        $unlinkedOnly = $request->boolean('unlinked');

        $query = MenuItem::query()
            ->with('category.outlet')
            ->whereHas('category.outlet', fn ($q) => $q->where('type', 'bar'))
            ->when($outletId, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('outlet_id', $outletId)))
            ->when($unlinkedOnly, fn ($q) => $q->whereDoesntHave('recipeIngredients'))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'ilike', '%'.trim((string) $request->query('q')).'%'))
            ->orderBy('name')
            ->limit(50);

        return response()->json($query->get(['id', 'name', 'category_id']));
    }

    public function index(Request $request): View
    {
        $outletId = $request->input('outlet_id');
        $sort     = in_array($request->query('sort'), ['name', 'price', 'created_at']) ? $request->query('sort') : 'name';
        $dir      = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search   = trim((string) $request->query('search', ''));

        $items = MenuItem::query()
            ->with(['category.outlet', 'recipeIngredients.stockItem', 'linkedStockItem'])
            ->when($outletId, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('outlet_id', $outletId)))
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->paginate(30)
            ->withQueryString();

        return view('modules.menu.index', [
            'items'    => $items,
            'outlets'  => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
            'outletId' => $outletId,
            'sort'     => $sort,
            'dir'      => $dir,
            'search'   => $search,
        ]);
    }

    public function create(Request $request): View
    {
        $outlets = Outlet::query()->where('is_active', true)->orderBy('name')->get();
        $categories = MenuCategory::query()
            ->with('outlet')
            ->when($request->filled('outlet_id'), fn ($q) => $q->where('outlet_id', $request->input('outlet_id')))
            ->orderBy('name')
            ->get();

        $outletId = $request->input('outlet_id');
        $outlet = $outletId ? Outlet::query()->find($outletId) : null;
        $tracksInventory = $outlet?->tracksBeverageInventory() ?? false;

        return view('modules.menu.form', [
            'item' => new MenuItem(['is_available' => true, 'price' => '0']),
            'outlets' => $outlets,
            'categories' => $categories,
            'tracksInventory' => $tracksInventory,
            'stockItems' => $tracksInventory && $outletId
                ? $this->beverageStockLink->stockItemsForBarOutlet($outletId)
                : collect(),
            'linkedStockItemId' => null,
            'serveQuantity' => old('serve_quantity', 1),
            'serveUnit' => old('serve_unit', 'bottle'),
            'inventoryLinkMode' => old('inventory_link_mode', 'awaiting'),
        ]);
    }

    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('menu-items/photos', MenuItem::photosDisk());
        }

        $menuItem = MenuItem::query()->create($validated);
        $menuItem->load('category.outlet');

        $this->syncBeverageInventory($menuItem, $request);

        return redirect()
            ->route('tenant.menu-items.index', ['outlet_id' => $request->input('outlet_filter')])
            ->with('success', 'Menu item created.');
    }

    public function edit(MenuItem $menuItem): View
    {
        $menuItem->load(['category.outlet', 'recipeIngredients.stockItem', 'linkedStockItem']);

        $outlet = $menuItem->category->outlet;
        $tracksInventory = $outlet->tracksBeverageInventory();
        $firstRecipe = $menuItem->recipeIngredients->first();

        return view('modules.menu.form', [
            'item' => $menuItem,
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
            'categories' => MenuCategory::query()->with('outlet')->where('outlet_id', $outlet->id)->orderBy('name')->get(),
            'tracksInventory' => $tracksInventory,
            'stockItems' => $tracksInventory
                ? $this->beverageStockLink->stockItemsForBarOutlet($outlet->id)
                : collect(),
            'linkedStockItemId' => old('linked_stock_item_id', $firstRecipe?->stock_item_id ?? $menuItem->linkedStockItem?->id),
            'serveQuantity' => old('serve_quantity', $firstRecipe?->quantity ?? 1),
            'serveUnit' => old('serve_unit', $firstRecipe?->unit ?? 'bottle'),
            'inventoryLinkMode' => old('inventory_link_mode', $firstRecipe ? 'existing' : ($menuItem->linkedStockItem?->awaiting_stock ? 'awaiting' : 'awaiting')),
        ]);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $validated = $request->validated();
        $disk = MenuItem::photosDisk();

        if ($request->boolean('remove_photo') && $menuItem->photo) {
            Storage::disk($disk)->delete($menuItem->photo);
            $validated['photo'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($menuItem->photo) {
                Storage::disk($disk)->delete($menuItem->photo);
            }
            $validated['photo'] = $request->file('photo')->store('menu-items/photos', $disk);
        }

        $menuItem->update($validated);
        $menuItem->load('category.outlet');

        $this->syncBeverageInventory($menuItem, $request);
        $menuItem->refresh();
        $this->beverageStockLink->mirrorMenuToLinkedStock($menuItem->load(['linkedStockItem', 'category']));
        $this->beverageStockLink->syncMenuAvailability($menuItem->load(['recipeIngredients.stockItem', 'category.outlet']));

        return redirect()
            ->route('tenant.menu-items.index', ['outlet_id' => $menuItem->category->outlet_id])
            ->with('success', 'Menu item updated.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $outletId = $menuItem->category->outlet_id;

        if ($menuItem->photo) {
            Storage::disk(MenuItem::photosDisk())->delete($menuItem->photo);
        }

        $this->beverageStockLink->detachStockForMenuItem($menuItem);
        $menuItem->delete();

        return redirect()
            ->route('tenant.menu-items.index', ['outlet_id' => $outletId])
            ->with('success', 'Menu item deleted.');
    }

    public function syncBarInventory(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage-menu') || $request->user()?->can('manage-inventory'), 403);

        $outletId = $request->input('outlet_id');
        $stats = $this->beverageStockLink->reconcileBarOutlet($outletId);

        $message = sprintf(
            'Bar menu and inventory synced: %d linked, %d inventory rows created, %d repaired, %d availability updates.',
            $stats['linked'],
            $stats['created'],
            $stats['repaired'],
            $stats['availability_updated']
        );

        return redirect()
            ->route('tenant.menu-items.index', ['outlet_id' => $outletId])
            ->with('success', $message);
    }

    private function syncBeverageInventory(MenuItem $menuItem, Request $request): void
    {
        if (! $menuItem->category->outlet->tracksBeverageInventory()) {
            return;
        }

        $mode = (string) $request->input('inventory_link_mode', 'awaiting');
        $linkedStockId = $request->input('linked_stock_item_id');
        $serveQty = (float) $request->input('serve_quantity', 1);
        $serveUnit = (string) $request->input('serve_unit', 'bottle');
        $recipe = $request->input('recipe', []);

        if ($mode === 'recipe') {
            $this->beverageStockLink->syncMenuInventory($menuItem, null, false, $serveQty, $serveUnit, $recipe);

            return;
        }

        if ($mode === 'existing') {
            $this->beverageStockLink->syncMenuInventory($menuItem, $linkedStockId, false, $serveQty, $serveUnit);

            return;
        }

        $this->beverageStockLink->syncMenuInventory($menuItem, null, true, $serveQty, $serveUnit);
    }
}
