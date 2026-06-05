<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Listeners;

use App\Domain\Inventory\Services\InventoryDeductionService;
use App\Domain\Restaurant\Events\OrderClosed;

class DeductInventoryOnOrderClose
{
    public function __construct(
        private readonly InventoryDeductionService $inventoryDeduction
    ) {}

    public function handle(OrderClosed $event): void
    {
        $this->inventoryDeduction->deductForOrder($event->order);
    }
}
