<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Models;

use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'nationality',
        'id_type',
        'id_number',
        'date_of_birth',
        'preferences',
        'vip_level',
    ];

    protected function casts(): array
    {
        return [
            'preferences' => 'array',
            'date_of_birth' => 'date',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    protected static function newFactory(): GuestFactory
    {
        return GuestFactory::new();
    }
}
