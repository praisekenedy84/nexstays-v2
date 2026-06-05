<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Exceptions;

class InsufficientStockException extends \DomainException
{
    public function __construct(
        public readonly string $stockItemName,
        public readonly float $required,
        public readonly float $available,
        public readonly string $unit,
    ) {
        parent::__construct(
            "Insufficient stock for {$stockItemName}: need {$required} {$unit}, have {$available} {$unit}."
        );
    }
}
