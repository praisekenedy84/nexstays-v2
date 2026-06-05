<?php

declare(strict_types=1);

namespace App\Domain\Till\Models;

use App\Domain\HBMS\Models\Folio;
use App\Domain\Shared\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'folio_id', 'order_id', 'till_session_id', 'amount', 'currency', 'method',
        'gateway', 'gateway_ref', 'gateway_response', 'cash_tendered', 'cash_change',
        'received_by', 'receipt_number', 'notes', 'status', 'fiscalized_at', 'fiscal_ref',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cash_tendered' => 'decimal:2',
            'cash_change' => 'decimal:2',
            'gateway_response' => 'array',
            'fiscalized_at' => 'datetime',
        ];
    }

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function tillSession(): BelongsTo
    {
        return $this->belongsTo(TillSession::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * POS payments and folio payments whose reservation still exists.
     */
    public function scopeForReporting($query)
    {
        return $query->where(function ($inner) {
            $inner->whereNull('folio_id')
                ->orWhereHas('folio.reservation');
        });
    }
}
