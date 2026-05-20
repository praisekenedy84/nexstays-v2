<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\DTO\TaxBreakdown;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

/**
 * Tax calculation entry point (NexStay TRD — never hard-code rates in actions).
 */
class TaxService
{
    public function calculate(Money $amount, string $chargeType): TaxBreakdown
    {
        $configs = config('nexstay.tax.default_rates', []);
        $rate = $configs[$chargeType] ?? $configs['_default'] ?? '0.18';
        $code = $configs['_code'] ?? 'A';
        $inclusive = (bool) ($configs['_inclusive'] ?? false);

        $taxMoney = $amount->multipliedBy($rate, RoundingMode::HALF_UP);

        return new TaxBreakdown(
            amount: $taxMoney,
            code: $code,
            rate: (string) $rate,
            isInclusive: $inclusive,
        );
    }
}
