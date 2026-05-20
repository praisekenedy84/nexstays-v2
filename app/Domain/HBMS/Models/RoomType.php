<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Models;

use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'max_adults',
        'max_children',
        'base_rate',
        'amenities',
        'photos',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'photos' => 'array',
            'base_rate' => 'decimal:2',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    protected static function newFactory(): RoomTypeFactory
    {
        return RoomTypeFactory::new();
    }
}
