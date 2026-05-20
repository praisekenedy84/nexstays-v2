<?php

declare(strict_types=1);

namespace App\Domain\Till\Models;

use App\Domain\Shared\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TillSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'outlet_id', 'opened_by', 'closed_by', 'float_amount', 'currency',
        'status', 'opened_at', 'closed_at', 'declared_cash', 'system_cash',
        'over_short', 'manager_notes',
    ];

    protected function casts(): array
    {
        return [
            'float_amount' => 'decimal:2',
            'declared_cash' => 'decimal:2',
            'system_cash' => 'decimal:2',
            'over_short' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(TillMovement::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
