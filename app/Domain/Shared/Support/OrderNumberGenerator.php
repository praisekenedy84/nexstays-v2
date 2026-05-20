<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

use App\Domain\Shared\Models\Order;
use Illuminate\Support\Facades\DB;

final class OrderNumberGenerator
{
    public static function generate(): string
    {
        $prefix = 'ORD-'.now()->format('ymd').'-';

        return DB::transaction(function () use ($prefix) {
            $last = Order::query()
                ->where('order_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('order_number')
                ->value('order_number');

            $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

            return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        });
    }
}
