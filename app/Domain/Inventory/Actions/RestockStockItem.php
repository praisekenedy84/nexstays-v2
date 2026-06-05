<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\StockItem;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Services\BeverageStockLinkService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestockStockItem
{
    public function __construct(
        private readonly BeverageStockLinkService $beverageStockLink
    ) {}

    public function execute(StockItem $stockItem, float $quantity, ?string $notes = null): StockItem
    {
        throw_if($quantity <= 0, \DomainException::class, 'Restock quantity must be greater than zero.');

        return DB::transaction(function () use ($stockItem, $quantity, $notes) {
            $performedBy = Auth::id();

            StockMovement::query()->create([
                'stock_item_id' => $stockItem->id,
                'movement_type' => 'restock',
                'quantity' => $quantity,
                'reference_id' => $stockItem->id,
                'reference_type' => StockItem::class,
                'notes' => $notes,
                'performed_by' => $performedBy,
            ]);

            $stockItem->increment('current_stock', $quantity);

            $stockItem->update([
                'last_restocked_at' => now(),
                'last_restocked_by' => $performedBy,
            ]);

            $stockItem = $stockItem->fresh();
            $this->beverageStockLink->clearAwaitingWhenStocked($stockItem);

            return $stockItem->load('lastRestockedBy');
        });
    }
}
