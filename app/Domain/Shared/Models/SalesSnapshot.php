<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class SalesSnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'rooms',
        'restaurant',
        'bar',
        'ancillary',
        'total',
        'room_nights',
        'payments_collected',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date'      => 'date',
            'rooms'              => 'decimal:2',
            'restaurant'         => 'decimal:2',
            'bar'                => 'decimal:2',
            'ancillary'          => 'decimal:2',
            'total'              => 'decimal:2',
            'payments_collected' => 'decimal:2',
        ];
    }
}
