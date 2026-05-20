<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'menu_item_id', 'quantity', 'unit_price', 'modifiers',
        'notes', 'course', 'status', 'sent_to_kds_at', 'prepared_at', 'served_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'modifiers' => 'array',
            'sent_to_kds_at' => 'datetime',
            'prepared_at' => 'datetime',
            'served_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
