<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Shared\Models\MenuItem;

class InventoryAvailabilityService
{
    public function assertCanFulfill(MenuItem $menuItem, int $quantity): void
    {
        $menuItem->loadMissing('recipeIngredients.stockItem');

        if ($menuItem->recipeIngredients->isEmpty()) {
            return;
        }

        foreach ($menuItem->recipeIngredients as $ingredient) {
            $required = (float) $ingredient->quantity * $quantity;
            $stockItem = $ingredient->stockItem;
            $available = (float) $stockItem->current_stock;

            if ($available < $required) {
                throw new InsufficientStockException(
                    $stockItem->name,
                    $required,
                    $available,
                    $stockItem->unit
                );
            }
        }
    }
}
