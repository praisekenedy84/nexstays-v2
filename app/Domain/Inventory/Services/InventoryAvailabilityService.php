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
            $stockItem = $ingredient->stockItem;
            if ($stockItem === null) {
                throw new InsufficientStockException('Unknown stock item', (float) $ingredient->quantity * $quantity, 0, $ingredient->unit ?? 'pcs');
            }

            $required = (float) $ingredient->quantity * $quantity;
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
