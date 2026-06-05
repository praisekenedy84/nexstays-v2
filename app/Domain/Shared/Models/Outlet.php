<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'type', 'floor_plan', 'is_active', 'settings'];

    protected function casts(): array
    {
        return [
            'floor_plan' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(OutletTable::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isRestaurant(): bool
    {
        return $this->type === 'restaurant';
    }

    public function isBar(): bool
    {
        return $this->type === 'bar';
    }

    public function isLounge(): bool
    {
        return $this->type === 'lounge';
    }

    /**
     * Recipe-based stock tracking applies to bar beverage sales only.
     * Restaurant and lounge orders are never blocked or deducted by inventory.
     */
    public function tracksBeverageInventory(): bool
    {
        return $this->isBar();
    }
}
