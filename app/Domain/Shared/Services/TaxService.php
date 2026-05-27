<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\DTO\TaxBreakdown;
use App\Domain\Shared\Services\TaxSettingsService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

/**
 * Tax calculation entry point. Rates and inclusive/exclusive mode are
 * read from per-tenant finance settings (TaxSettingsService), falling
 * back to the nexstay.tax config when no tenant is active.
 */
class TaxService
{
    public function __construct(
        private readonly TaxSettingsService $taxSettings,
    ) {}

    public function calculate(Money $amount, string $chargeType): TaxBreakdown
    {
        $settings  = $this->taxSettings->all();
        $rate      = (string) ($settings['rates'][$chargeType] ?? $settings['vat_rate'] ?? '0.18');
        $code      = $settings['tax_code'] ?? 'A';
        $inclusive = (bool) ($settings['tax_inclusive'] ?? true);

        if ($inclusive) {
            // Price already contains tax — extract it: tax = total × rate / (1 + rate)
            // e.g. 1,180 at 18% → tax = 1,180 × 0.18 / 1.18 = 180
            $divisor  = bcadd('1', $rate, 10);
            $taxMoney = $amount->multipliedBy($rate, RoundingMode::HALF_UP)
                                ->dividedBy($divisor, RoundingMode::HALF_UP);
        } else {
            // Price is net — calculate tax on top: tax = net × rate
            $taxMoney = $amount->multipliedBy($rate, RoundingMode::HALF_UP);
        }

        return new TaxBreakdown(
            amount: $taxMoney,
            code: $code,
            rate: $rate,
            isInclusive: $inclusive,
        );
    }
}
