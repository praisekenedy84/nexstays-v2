<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Actions;

use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Purchases\Models\PurchaseOrder;
use App\Domain\Purchases\Models\PurchaseOrderLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceivePurchaseOrder
{
    public function execute(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        throw_if(
            ! $purchaseOrder->isReceivable(),
            \DomainException::class,
            "Purchase order {$purchaseOrder->po_number} cannot be received (status: {$purchaseOrder->status})."
        );

        return DB::transaction(function () use ($purchaseOrder) {
            $purchaseOrder->load('lines.stockItem');

            foreach ($purchaseOrder->lines as $line) {
                $this->receiveLine($purchaseOrder, $line);
            }

            $purchaseOrder->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            return $purchaseOrder->fresh(['lines.stockItem', 'outlet', 'creator']);
        });
    }

    private function receiveLine(PurchaseOrder $purchaseOrder, PurchaseOrderLine $line): void
    {
        $stockItem = $line->stockItem;
        $qty = (float) $line->quantity;

        StockMovement::query()->create([
            'stock_item_id' => $stockItem->id,
            'movement_type' => 'purchase',
            'quantity' => $qty,
            'reference_id' => $purchaseOrder->id,
            'reference_type' => PurchaseOrder::class,
            'notes' => "PO {$purchaseOrder->po_number}",
            'performed_by' => Auth::id(),
        ]);

        $stockItem->increment('current_stock', $qty);

        if ($line->unit_cost) {
            $stockItem->update(['cost_per_unit' => $line->unit_cost]);
        }
    }
}
