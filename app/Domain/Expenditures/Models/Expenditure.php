<?php

declare(strict_types=1);

namespace App\Domain\Expenditures\Models;

use App\Domain\Shared\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expenditure extends Model
{
    use HasUuids;
    use SoftDeletes;

    public const CATEGORIES = ['utilities', 'salaries', 'maintenance', 'supplies', 'other'];

    protected $fillable = [
        'category', 'outlet_id', 'amount', 'currency', 'description',
        'reference', 'expense_date', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
