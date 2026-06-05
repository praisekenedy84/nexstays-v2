<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\RecipeIngredient;
use App\Domain\Shared\Models\MenuItem;
use Illuminate\Support\Facades\DB;

class SyncMenuItemRecipe
{
    /**
     * @param  list<array{stock_item_id?: string|null, quantity?: float|string|null, unit?: string|null}>  $lines
     */
    public function execute(MenuItem $menuItem, array $lines): void
    {
        DB::transaction(function () use ($menuItem, $lines) {
            RecipeIngredient::query()
                ->where('menu_item_id', $menuItem->id)
                ->delete();

            foreach ($lines as $line) {
                $stockItemId = $line['stock_item_id'] ?? null;
                $quantity = isset($line['quantity']) ? (float) $line['quantity'] : 0;

                if ($stockItemId === null || $stockItemId === '' || $quantity <= 0) {
                    continue;
                }

                RecipeIngredient::query()->create([
                    'menu_item_id' => $menuItem->id,
                    'stock_item_id' => $stockItemId,
                    'quantity' => $quantity,
                    'unit' => $line['unit'] ?? 'pcs',
                ]);
            }
        });
    }
}
