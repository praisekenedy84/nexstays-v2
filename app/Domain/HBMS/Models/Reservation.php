<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Models;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'booking_ref',
        'guest_id',
        'room_id',
        'room_type_id',
        'status',
        'cancelled_at',
        'checked_in_at',
        'checked_out_at',
        'cancellation_policy',
        'cancellation_nights_used',
        'prepaid_amount_at_cancellation',
        'cancellation_charge_amount',
        'cancellation_refund_amount',
        'cancellation_refund_percentage',
        'check_in_date',
        'check_out_date',
        'adults',
        'children',
        'rate_plan_id',
        'daily_rate',
        'source',
        'ota_ref',
        'special_requests',
        'deposit_amount',
        'created_by',
        'overstay_nights',
        'overstay_rate',
        'overstay_charge',
        'overstay_settlement',
        'overstay_notes',
        'overstay_waiver_reason',
        'overstay_charge_transaction_id',
        'overstay_settlement_transaction_id',
        'overstay_settlement_payment_id',
        'overstay_settled_by',
        'overstay_settled_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'cancelled_at'   => 'datetime',
            'checked_in_at'  => 'datetime',
            'checked_out_at' => 'datetime',
            'daily_rate' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'prepaid_amount_at_cancellation' => 'decimal:2',
            'cancellation_charge_amount' => 'decimal:2',
            'cancellation_refund_amount' => 'decimal:2',
            'cancellation_refund_percentage' => 'decimal:2',
            'overstay_rate'       => 'decimal:2',
            'overstay_charge'     => 'decimal:2',
            'overstay_settled_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function folio(): HasOne
    {
        return $this->hasOne(Folio::class);
    }

    public function overstayChargeTransaction(): BelongsTo
    {
        return $this->belongsTo(FolioTransaction::class, 'overstay_charge_transaction_id');
    }

    public function overstaySettlementTransaction(): BelongsTo
    {
        return $this->belongsTo(FolioTransaction::class, 'overstay_settlement_transaction_id');
    }

    public function overstaySettlementPayment(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Till\Models\Payment::class, 'overstay_settlement_payment_id');
    }

    public function overstaySettledBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'overstay_settled_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Extra nights and charge beyond the booked check-out date.
     *
     * @return array{nights: int, rate: float, charge: float, status: string}|null
     */
    public function overstayIncrease(): ?array
    {
        if ($this->overstay_settlement !== null) {
            return [
                'nights' => (int) ($this->overstay_nights ?? 0),
                'rate'   => (float) ($this->overstay_rate ?? 0),
                'charge' => (float) ($this->overstay_charge ?? 0),
                'status' => (string) $this->overstay_settlement,
            ];
        }

        $preview = $this->overstayPreview();

        if ($preview === null) {
            return null;
        }

        return [
            'nights' => $preview['nights'],
            'rate'   => $preview['rate'],
            'charge' => $preview['charge'],
            'status' => 'detected',
        ];
    }

    public function getTotalNightsAttribute(): int
    {
        if ($this->check_in_date === null || $this->check_out_date === null) {
            return 0;
        }

        return (int) max(0, (int) $this->check_in_date->diffInDays($this->check_out_date));
    }

    public function getTotalAmountAttribute(): string
    {
        if ($this->total_nights === 0) {
            return '0.00';
        }

        return Money::of((string) ($this->daily_rate ?? 0), 'TZS')
            ->multipliedBy((string) $this->total_nights, RoundingMode::HALF_UP)
            ->getAmount()
            ->__toString();
    }

    public function isOverstay(): bool
    {
        return $this->status === 'checked_in'
            && $this->check_out_date !== null
            && now()->startOfDay()->gt($this->check_out_date->startOfDay());
    }

    /**
     * @return array{nights: int, rate: float, charge: float}|null
     */
    public function overstayPreview(?float $rateOverride = null): ?array
    {
        if (! $this->isOverstay()) {
            return null;
        }

        $checkOutDate = $this->check_out_date->startOfDay();
        $today        = now()->startOfDay();
        $nights       = (int) $checkOutDate->diffInDays($today);

        if ($nights <= 0) {
            return null;
        }

        $dailyRate   = $rateOverride ?? (float) ($this->daily_rate ?? 0);
        $totalCharge = round($dailyRate * $nights, 2);

        return [
            'nights' => $nights,
            'rate'   => $dailyRate,
            'charge' => $totalCharge,
        ];
    }

    public function hasPendingOverstay(): bool
    {
        return $this->overstay_settlement === 'pending';
    }

    public function isOverstaySettled(): bool
    {
        return in_array($this->overstay_settlement, ['paid', 'waived'], true);
    }

    protected static function newFactory(): ReservationFactory
    {
        return ReservationFactory::new();
    }
}
