<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Domain\Shared\Services\TaxService;
use App\Domain\Shared\Services\TaxSettingsService;
use Brick\Money\Money;
use Tests\TenantTestCase;

class TaxServiceTest extends TenantTestCase
{
    /**
     * Regression: with the default 18% inclusive rate, a 1,180 charge
     * contains exactly 180 of tax.
     */
    public function test_calculate_returns_correct_tax_for_inclusive_rate(): void
    {
        $breakdown = app(TaxService::class)->calculate(Money::of('1180', 'TZS'), 'room_charge');

        $this->assertTrue($breakdown->amount->isEqualTo(Money::of('180', 'TZS')));
        $this->assertTrue($breakdown->isInclusive);
        $this->assertSame('0.18', $breakdown->rate);
    }

    /**
     * SHARED-2: a corrupted/non-numeric tenant tax rate must not throw an
     * uncaught Brick\Math/Brick\Money exception — TaxService should fall back
     * to the configured default rate instead.
     */
    public function test_calculate_falls_back_to_default_for_invalid_rate(): void
    {
        $this->mock(TaxSettingsService::class, function ($mock): void {
            $mock->shouldReceive('all')->andReturn([
                'tax_inclusive' => true,
                'vat_rate' => 'not-a-number',
                'tax_code' => 'A',
                'rates' => [
                    'room_charge' => 'not-a-number',
                    'restaurant' => '0.18',
                    'bar' => '0.18',
                ],
            ]);
        });

        $breakdown = app(TaxService::class)->calculate(Money::of('1180', 'TZS'), 'room_charge');

        $this->assertTrue($breakdown->amount->isEqualTo(Money::of('180', 'TZS')));
    }
}
