<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Actions;

use App\Domain\Purchases\Models\PurchaseOrder;
use App\Domain\Purchases\Models\PurchaseOrderLine;
use App\Domain\Purchases\Support\PurchaseOrderNumberGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreatePurchaseOrder
{
    public function __construct(
        private readonly PurchaseOrderNumberGenerator $numberGenerator
    ) {}

    /**
     * @param  array{department: string, supplier_name: string, supplier_reference?: string|null, outlet_id?: string|null, delivery_date_expected?: string|null, payment_terms?: string|null, notes?: string|null, lines: list<array{stock_item_id: string, quantity: float|int|string, unit_cost: float|int|string, line_notes?: string|null}>}  $data
     */
    public function execute(array $data, bool $receiveImmediately = false): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $receiveImmediately) {
            $currency = config('nexstay.currency.default', 'TZS');
            $total = 0.0;

            foreach ($data['lines'] as $line) {
                $total += (float) $line['quantity'] * (float) $line['unit_cost'];
            }

            $purchaseOrder = PurchaseOrder::query()->create([
                'po_number'                => $this->numberGenerator->generate(),
                'outlet_id'                => $data['outlet_id'] ?? null,
                'department'               => $data['department'],
                'supplier_name'            => $data['supplier_name'],
                'supplier_reference'       => $data['supplier_reference'] ?? null,
                'status'                   => $receiveImmediately ? 'ordered' : 'draft',
                'total_amount'             => $total,
                'currency'                 => $currency,
                'ordered_at'               => $receiveImmediately ? now() : null,
                'delivery_date_expected'   => $data['delivery_date_expected'] ?? null,
                'payment_terms'            => $data['payment_terms'] ?? null,
                'notes'                    => $data['notes'] ?? null,
                'created_by'               => Auth::id(),
            ]);

            foreach ($data['lines'] as $line) {
                $lineTotal = (float) $line['quantity'] * (float) $line['unit_cost'];
                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'stock_item_id'     => $line['stock_item_id'],
                    'quantity'          => $line['quantity'],
                    'unit_cost'         => $line['unit_cost'],
                    'line_total'        => $lineTotal,
                    'line_notes'        => $line['line_notes'] ?? null,
                ]);
            }

            if ($receiveImmediately) {
                return app(ReceivePurchaseOrder::class)->execute($purchaseOrder);
            }

            return $purchaseOrder->load(['lines.stockItem', 'outlet']);
        });
    }
}
