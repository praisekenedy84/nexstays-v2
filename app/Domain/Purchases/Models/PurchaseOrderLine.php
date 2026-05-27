<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Models;

use App\Domain\Inventory\Models\StockItem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    use HasUuids;

    protected $fillable = [
        'purchase_order_id', 'stock_item_id', 'quantity', 'unit_cost', 'line_total',
        'qty_received', 'actual_unit_cost', 'line_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'         => 'decimal:3',
            'unit_cost'        => 'decimal:4',
            'line_total'       => 'decimal:2',
            'qty_received'     => 'decimal:3',
            'actual_unit_cost' => 'decimal:4',
        ];
    }

    public function variancePct(): ?float
    {
        if (! $this->actual_unit_cost || ! $this->unit_cost || (float) $this->unit_cost === 0.0) {
            return null;
        }

        return (((float) $this->actual_unit_cost - (float) $this->unit_cost) / (float) $this->unit_cost) * 100;
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}
