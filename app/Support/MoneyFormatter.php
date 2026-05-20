<?php

declare(strict_types=1);

namespace App\Support;

final class MoneyFormatter
{
    public static function format(string|float|int|null $amount, ?string $currency = null): string
    {
        $currency ??= config('nexstay.currency.default', 'TZS');
        $value = (float) ($amount ?? 0);

        return $currency.' '.number_format($value, 0, '.', ',');
    }

    public static function perNight(string|float|int|null $amount, ?string $currency = null): string
    {
        return self::format($amount, $currency).'/night';
    }
}
