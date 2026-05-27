<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Inventory\Models\StockItem;
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
    public function json(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $items = StockItem::query()
            ->when($q, fn ($query) => $query->where('name', 'ilike', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'unit']);

        return response()->json($items);
    }

    public function index(Request $request): View
    {
        $sort   = in_array($request->query('sort'), ['name', 'category', 'current_stock', 'reorder_level']) ? $request->query('sort') : 'name';
        $dir    = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $items = StockItem::query()
            ->with('outlet')
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->paginate(30)
            ->withQueryString();

        $lowStock = StockItem::query()
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->count();

        return view('modules.inventory.index', compact('items', 'lowStock', 'sort', 'dir', 'search'));
    }

    public function create(): View
    {
        return view('modules.inventory.form', [
            'stockItem' => new StockItem,
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreStockItemRequest $request): RedirectResponse
    {
        StockItem::query()->create($request->validated());

        return redirect()
            ->route('tenant.stock-items.index')
            ->with('success', 'Stock item created.');
    }

    public function edit(StockItem $stockItem): View
    {
        return view('modules.inventory.form', [
            'stockItem' => $stockItem,
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateStockItemRequest $request, StockItem $stockItem): RedirectResponse
    {
        $stockItem->update($request->validated());

        return redirect()
            ->route('tenant.stock-items.index')
            ->with('success', 'Stock item updated.');
    }

    public function destroy(StockItem $stockItem): RedirectResponse
    {
        $stockItem->delete();

        return redirect()
            ->route('tenant.stock-items.index')
            ->with('success', 'Stock item removed.');
    }
}
