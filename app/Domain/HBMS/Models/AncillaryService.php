<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AncillaryService extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'default_price', 'currency', 'charge_type', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
