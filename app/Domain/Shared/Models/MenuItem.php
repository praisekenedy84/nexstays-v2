<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Inventory\Models\RecipeIngredient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MenuItem extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'description', 'photo', 'price', 'cost', 'sku',
        'allergens', 'tags', 'is_available', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'allergens' => 'array',
            'tags' => 'array',
            'is_available' => 'boolean',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
