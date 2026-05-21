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
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'cancelled_at' => 'datetime',
            'daily_rate' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'prepaid_amount_at_cancellation' => 'decimal:2',
            'cancellation_charge_amount' => 'decimal:2',
            'cancellation_refund_amount' => 'decimal:2',
            'cancellation_refund_percentage' => 'decimal:2',
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

    protected static function newFactory(): ReservationFactory
    {
        return ReservationFactory::new();
    }
}
