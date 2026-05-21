<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Room extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'room_type_id',
        'room_number',
        'floor',
        'status',
        'daily_rate',
        'photos',
        'amenities',
        'features',
        'is_smoking',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_smoking' => 'boolean',
            'daily_rate' => 'decimal:2',
            'photos' => 'array',
            'amenities' => 'array',
            'features' => 'array',
        ];
    }

    public static function photosDisk(): string
    {
        $disk = (string) config('filesystems.default', 'public');

        return $disk === 'local' ? 'public' : $disk;
    }

    public static function photoUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $disk = self::photosDisk();

        if (in_array($disk, ['local', 'public'], true)) {
            if (function_exists('tenant') && tenant() !== null) {
                return url('/tenancy/assets/'.ltrim($path, '/'));
            }

            return url('/storage/'.ltrim($path, '/'));
        }

        $url = Storage::disk($disk)->url($path);

        if (
            str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://')
            || str_starts_with($url, '//')
        ) {
            return $url;
        }

        return url($url);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    protected static function newFactory(): RoomFactory
    {
        return RoomFactory::new();
    }
}
