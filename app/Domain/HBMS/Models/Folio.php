<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folio extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'reservation_id',
        'folio_number',
        'status',
        'currency',
        'pre_auth_amount',
        'settled_amount',
    ];

    protected function casts(): array
    {
        return [
            'pre_auth_amount' => 'decimal:2',
            'settled_amount' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FolioTransaction::class);
    }
}
