<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'outlet_id', 'menu_item_id', 'name', 'category', 'unit', 'reorder_level',
        'current_stock', 'awaiting_stock', 'last_restocked_at', 'last_restocked_by',
        'cost_per_unit',
    ];

    protected function casts(): array
    {
        return [
            'reorder_level' => 'decimal:3',
            'current_stock' => 'decimal:3',
            'cost_per_unit' => 'decimal:4',
            'awaiting_stock' => 'boolean',
            'last_restocked_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function lastRestockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_restocked_by');
    }
}
