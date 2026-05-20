<?php

declare(strict_types=1);

namespace App\Domain\Shared\DTO;

use Brick\Money\Money;

final readonly class TaxBreakdown
{
    public function __construct(
        public Money $amount,
        public string $code,
        public string $rate,
        public bool $isInclusive,
    ) {}
}
