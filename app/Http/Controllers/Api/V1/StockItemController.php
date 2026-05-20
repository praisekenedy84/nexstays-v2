<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Inventory\Models\StockItem;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Web\StoreStockItemRequest;
use App\Http\Requests\Web\UpdateStockItemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockItemController extends Controller
{
    use RespondsWithJsonApi;

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-inventory'), 403);

        $items = StockItem::query()
            ->when($request->filled('outlet_id'), fn ($q) => $q->where('outlet_id', $request->input('outlet_id')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 50), 100));

        return $this->respondCollection($items->through(fn (StockItem $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category,
            'unit' => $item->unit,
            'current_stock' => $item->current_stock,
            'reorder_level' => $item->reorder_level,
            'low_stock' => (float) $item->current_stock <= (float) $item->reorder_level,
        ]));
    }

    public function store(StoreStockItemRequest $request): JsonResponse
    {
        $item = StockItem::query()->create($request->validated());

        return $this->respond(['id' => $item->id, 'name' => $item->name], 201);
    }

    public function update(UpdateStockItemRequest $request, StockItem $stockItem): JsonResponse
    {
        $stockItem->update($request->validated());

        return $this->respond(['id' => $stockItem->id, 'name' => $stockItem->name]);
    }

    public function destroy(Request $request, StockItem $stockItem): JsonResponse
    {
        abort_unless($request->user()?->can('manage-inventory'), 403);

        $stockItem->delete();

        return $this->respond(['message' => 'Stock item deleted.']);
    }
}
