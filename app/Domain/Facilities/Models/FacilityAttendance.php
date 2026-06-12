<?php

declare(strict_types=1);

namespace App\Domain\Facilities\Models;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Guest;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\Till\Models\Payment;
use App\Domain\Till\Models\TillSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacilityAttendance extends Model
{
    use HasUuids;
    use SoftDeletes;

    public const TYPES = ['pool', 'gym'];

    public const SETTLEMENTS = ['cash', 'mobile_money', 'card', 'folio', 'complimentary'];

    protected $fillable = [
        'facility_type',
        'visitor_name',
        'party_size',
        'guest_id',
        'reservation_id',
        'amount',
        'currency',
        'settlement',
        'payment_id',
        'folio_transaction_id',
        'till_session_id',
        'notes',
        'recorded_by',
        'attended_at',
        'voided_at',
        'void_reason',
        'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'party_size' => 'integer',
            'attended_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function folioTransaction(): BelongsTo
    {
        return $this->belongsTo(FolioTransaction::class);
    }

    public function tillSession(): BelongsTo
    {
        return $this->belongsTo(TillSession::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function typeLabel(): string
    {
        return match ($this->facility_type) {
            'pool' => 'Swimming Pool',
            'gym' => 'Gym',
            default => ucfirst($this->facility_type),
        };
    }
}
