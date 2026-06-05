<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Inventory\Actions\RestockStockItem;
use App\Domain\Inventory\Models\StockItem;
use App\Domain\Inventory\Services\BeverageStockLinkService;
use App\Http\Requests\Web\RestockStockItemRequest;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreStockItemRequest;
use App\Http\Requests\Web\UpdateStockItemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockItemController extends Controller
{
    public function __construct(
        private readonly BeverageStockLinkService $beverageStockLink,
        private readonly RestockStockItem $restockStockItem,
    ) {}

    public function json(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $outletId = $request->query('outlet_id');

        $items = StockItem::query()
            ->when($outletId, fn ($query) => $query->where(fn ($q2) => $q2->where('outlet_id', $outletId)->orWhereNull('outlet_id')))
            ->when($q, fn ($query) => $query->where('name', 'ilike', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'unit', 'current_stock', 'awaiting_stock']);

        return response()->json($items);
    }

    public function index(Request $request): View
    {
        $sort   = in_array($request->query('sort'), ['name', 'category', 'current_stock', 'reorder_level']) ? $request->query('sort') : 'name';
        $dir    = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));
        $outletId = $request->query('outlet_id');

        $items = StockItem::query()
            ->with(['outlet', 'menuItem', 'lastRestockedBy'])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->where('awaiting_stock', false)
            ->orderBy($sort, $dir)
            ->paginate(30)
            ->withQueryString();

        $lowStock = StockItem::query()
            ->where('awaiting_stock', false)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->count();

        $awaitingStock = StockItem::query()
            ->with(['outlet', 'menuItem', 'lastRestockedBy'])
            ->where('awaiting_stock', true)
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->orderBy('name')
            ->get();
        $unlinkedMenuItems = $this->beverageStockLink->unlinkedBarMenuItems($outletId);
        $barOutlets = $this->beverageStockLink->barOutlets();

        return view('modules.inventory.index', compact(
            'items',
            'lowStock',
            'sort',
            'dir',
            'search',
            'awaitingStock',
            'unlinkedMenuItems',
            'barOutlets',
            'outletId',
        ));
    }

    public function create(Request $request): View
    {
        $menuItem = $request->filled('menu_item_id')
            ? MenuItem::query()->with('category.outlet')->find($request->input('menu_item_id'))
            : null;

        $stockItem = new StockItem([
            'name' => $menuItem?->name ?? '',
            'category' => 'beverage',
            'unit' => 'bottle',
            'current_stock' => 0,
            'awaiting_stock' => true,
            'outlet_id' => $menuItem?->category?->outlet_id ?? $request->input('outlet_id'),
        ]);

        return view('modules.inventory.form', [
            'stockItem' => $stockItem,
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
            'barOutlets' => $this->beverageStockLink->barOutlets(),
            'unlinkedMenuItems' => $this->beverageStockLink->unlinkedBarMenuItems($stockItem->outlet_id),
            'prefillMenuItemId' => $menuItem?->id,
            'serveQuantity' => old('serve_quantity', 1),
            'serveUnit' => old('serve_unit', 'bottle'),
        ]);
    }

    public function store(StoreStockItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['awaiting_stock'] = (float) ($data['current_stock'] ?? 0) <= 0;

        $stockItem = StockItem::query()->create($data);

        $this->beverageStockLink->syncStockMenuLink(
            $stockItem,
            $request->input('menu_item_id'),
            (float) $request->input('serve_quantity', 1),
            (string) $request->input('serve_unit', 'bottle')
        );

        $stockItem = $stockItem->fresh();
        $this->beverageStockLink->clearAwaitingWhenStocked($stockItem);
        $this->beverageStockLink->syncStockItem($stockItem);

        return redirect()
            ->route('tenant.stock-items.index', ['outlet_id' => $stockItem->outlet_id])
            ->with('success', 'Stock item created.');
    }

    public function edit(StockItem $stockItem): View
    {
        $stockItem->load(['menuItem', 'lastRestockedBy']);

        $firstRecipe = $stockItem->menuItem?->recipeIngredients()->where('stock_item_id', $stockItem->id)->first();

        $unlinkedMenuItems = $this->beverageStockLink->unlinkedBarMenuItems($stockItem->outlet_id);
        if ($stockItem->menuItem !== null) {
            $unlinkedMenuItems = $unlinkedMenuItems
                ->push($stockItem->menuItem)
                ->unique('id')
                ->sortBy('name')
                ->values();
        }

        return view('modules.inventory.form', [
            'stockItem' => $stockItem,
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
            'barOutlets' => $this->beverageStockLink->barOutlets(),
            'unlinkedMenuItems' => $unlinkedMenuItems,
            'prefillMenuItemId' => old('menu_item_id', $stockItem->menu_item_id),
            'serveQuantity' => old('serve_quantity', $firstRecipe?->quantity ?? 1),
            'serveUnit' => old('serve_unit', $firstRecipe?->unit ?? 'bottle'),
        ]);
    }

    public function update(UpdateStockItemRequest $request, StockItem $stockItem): RedirectResponse
    {
        $data = $request->validated();
        $stockItem->update($data);

        $this->beverageStockLink->syncStockMenuLink(
            $stockItem->fresh(),
            $request->input('menu_item_id'),
            (float) $request->input('serve_quantity', 1),
            (string) $request->input('serve_unit', 'bottle')
        );

        $stockItem = $stockItem->fresh();
        $this->beverageStockLink->clearAwaitingWhenStocked($stockItem);
        $this->beverageStockLink->syncStockItem($stockItem);

        return redirect()
            ->route('tenant.stock-items.index', ['outlet_id' => $stockItem->outlet_id])
            ->with('success', 'Stock item updated.');
    }

    public function sync(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage-inventory'), 403);

        $outletId = $request->input('outlet_id');
        $stats = $this->beverageStockLink->reconcileBarOutlet($outletId);

        $message = sprintf(
            'Synced bar inventory: %d linked, %d created, %d repaired, %d menu availability updates.',
            $stats['linked'],
            $stats['created'],
            $stats['repaired'],
            $stats['availability_updated']
        );

        return redirect()
            ->route('tenant.stock-items.index', ['outlet_id' => $outletId])
            ->with('success', $message);
    }

    public function destroy(StockItem $stockItem): RedirectResponse
    {
        $outletId = $stockItem->outlet_id;
        $stockItem->delete();

        return redirect()
            ->route('tenant.stock-items.index', ['outlet_id' => $outletId])
            ->with('success', 'Stock item removed.');
    }

    public function restockForm(StockItem $stockItem): View
    {
        abort_unless(auth()->user()?->can('manage-inventory'), 403);

        $stockItem->load(['outlet', 'menuItem', 'lastRestockedBy']);

        return view('modules.inventory.restock', compact('stockItem'));
    }

    public function restock(RestockStockItemRequest $request, StockItem $stockItem): RedirectResponse
    {
        try {
            $this->restockStockItem->execute(
                $stockItem,
                (float) $request->validated('quantity'),
                $request->validated('notes')
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.stock-items.index', ['outlet_id' => $stockItem->outlet_id])
            ->with('success', "{$stockItem->name} restocked successfully.");
    }
}
