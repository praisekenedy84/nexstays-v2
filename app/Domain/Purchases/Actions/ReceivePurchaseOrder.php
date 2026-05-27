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
    /**
     * @param  list<array{line_id: string, qty_received: float|string, actual_unit_cost: float|string, line_notes?: string|null}>  $lineData
     */
    public function execute(PurchaseOrder $purchaseOrder, array $lineData = []): PurchaseOrder
    {
        throw_if(
            ! $purchaseOrder->isReceivable(),
            \DomainException::class,
            "Purchase order {$purchaseOrder->po_number} cannot be received (status: {$purchaseOrder->status})."
        );

        return DB::transaction(function () use ($purchaseOrder, $lineData) {
            $purchaseOrder->load('lines.stockItem');

            $keyedData = collect($lineData)->keyBy('line_id');
            $receivedTotal = 0.0;

            foreach ($purchaseOrder->lines as $line) {
                $entry        = $keyedData->get($line->id, []);
                $qtyReceived  = isset($entry['qty_received'])     ? (float) $entry['qty_received']     : (float) $line->quantity;
                $actualCost   = isset($entry['actual_unit_cost']) ? (float) $entry['actual_unit_cost'] : (float) $line->unit_cost;
                $lineNotes    = $entry['line_notes'] ?? null;

                $line->update([
                    'qty_received'     => $qtyReceived,
                    'actual_unit_cost' => $actualCost,
                    'line_notes'       => $lineNotes,
                ]);

                $receivedTotal += $qtyReceived * $actualCost;

                if ($qtyReceived > 0) {
                    $this->receiveLineStock($purchaseOrder, $line, $qtyReceived, $actualCost);
                }
            }

            $purchaseOrder->update([
                'status'         => 'received',
                'received_at'    => now(),
                'received_total' => $receivedTotal,
            ]);

            return $purchaseOrder->fresh(['lines.stockItem', 'outlet', 'creator']);
        });
    }

    private function receiveLineStock(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderLine $line,
        float $qty,
        float $cost
    ): void {
        $stockItem = $line->stockItem;

        StockMovement::query()->create([
            'stock_item_id'  => $stockItem->id,
            'movement_type'  => 'purchase',
            'quantity'       => $qty,
            'reference_id'   => $purchaseOrder->id,
            'reference_type' => PurchaseOrder::class,
            'notes'          => "PO {$purchaseOrder->po_number}",
            'performed_by'   => Auth::id(),
        ]);

        $stockItem->increment('current_stock', $qty);

        if ($cost > 0) {
            $stockItem->update(['cost_per_unit' => $cost]);
        }
    }
}
