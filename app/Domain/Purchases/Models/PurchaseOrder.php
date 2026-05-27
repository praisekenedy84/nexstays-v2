<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Models;

use App\Domain\Shared\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'po_number', 'outlet_id', 'department', 'supplier_name', 'supplier_reference',
        'status', 'total_amount', 'received_total', 'currency',
        'ordered_at', 'delivery_date_expected', 'payment_terms', 'received_at',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount'           => 'decimal:2',
            'received_total'         => 'decimal:2',
            'ordered_at'             => 'datetime',
            'received_at'            => 'datetime',
            'delivery_date_expected' => 'date',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isReceivable(): bool
    {
        return in_array($this->status, ['draft', 'ordered'], true);
    }

    public function costVariance(): float
    {
        if (! $this->received_total || ! $this->total_amount) {
            return 0.0;
        }

        return (float) $this->received_total - (float) $this->total_amount;
    }

    public function costVariancePct(): ?float
    {
        if (! $this->total_amount || (float) $this->total_amount === 0.0) {
            return null;
        }

        return ($this->costVariance() / (float) $this->total_amount) * 100;
    }
}
