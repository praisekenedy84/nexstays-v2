<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Support;

use App\Domain\HBMS\Models\Reservation;
use Illuminate\Support\Facades\DB;

final class BookingReferenceGenerator
{
    public static function generate(): string
    {
        $prefix = 'NXS-'.now()->format('Y').'-';

        return DB::transaction(function () use ($prefix) {
            $last = Reservation::query()
                ->where('booking_ref', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('booking_ref')
                ->value('booking_ref');

            $seq = $last ? ((int) substr($last, -5)) + 1 : 1;

            return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
        });
    }
}
