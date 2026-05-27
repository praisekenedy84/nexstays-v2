<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Models;

use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
                return route('tenant.assets', ['tenantId' => tenant('id'), 'path' => ltrim($path, '/')]);
            }

            return url('/storage/'.ltrim($path, '/'));
        }

        $url = Storage::disk($disk)->url($path);

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//')) {
            return $url;
        }

        return url($url);
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
