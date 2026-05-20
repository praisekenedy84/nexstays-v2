<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RatePlan extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'currency',
        'is_active',
        'valid_from',
        'valid_to',
        'restrictions',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'restrictions' => 'array',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(RatePlanPrice::class);
    }
}
