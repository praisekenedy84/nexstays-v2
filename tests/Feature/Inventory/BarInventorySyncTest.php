<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\Services\BeverageStockLinkService;
use App\Domain\Inventory\Services\InventoryDeductionService;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Inventory\Models\RecipeIngredient;
use App\Domain\Inventory\Models\StockItem;
use Tests\TenantTestCase;

class BarInventorySyncTest extends TenantTestCase
{
    public function test_reconcile_creates_inventory_for_unlinked_bar_menu_item(): void
    {
        $bar = Outlet::query()->create(['name' => 'Bar', 'type' => 'bar', 'is_active' => true]);
        $cat = $bar->menuCategories()->create(['name' => 'Drinks', 'display_order' => 1]);
        $menu = MenuItem::query()->create([
            'category_id' => $cat->id,
            'name' => 'Tusker',
            'price' => '5000.00',
            'is_available' => true,
        ]);

        $stats = app(BeverageStockLinkService::class)->reconcileBarOutlet($bar->id);

        $this->assertSame(1, $stats['created']);
        $menu->load('recipeIngredients.stockItem', 'linkedStockItem');
        $this->assertCount(1, $menu->recipeIngredients);
        $this->assertEquals('Tusker', $menu->linkedStockItem?->name);
    }

    public function test_deduction_marks_menu_unavailable_when_stock_depleted(): void
    {
        $bar = Outlet::query()->create(['name' => 'Bar', 'type' => 'bar', 'is_active' => true]);
        $cat = $bar->menuCategories()->create(['name' => 'Drinks', 'display_order' => 1]);
        $menu = MenuItem::query()->create([
            'category_id' => $cat->id,
            'name' => 'Soda',
            'price' => '3000.00',
            'is_available' => true,
        ]);

        $stock = StockItem::query()->create([
            'outlet_id' => $bar->id,
            'menu_item_id' => $menu->id,
            'name' => 'Soda',
            'unit' => 'bottle',
            'current_stock' => 1,
            'reorder_level' => 0,
        ]);

        RecipeIngredient::query()->create([
            'menu_item_id' => $menu->id,
            'stock_item_id' => $stock->id,
            'quantity' => 1,
            'unit' => 'bottle',
        ]);

        $order = Order::query()->create([
            'outlet_id' => $bar->id,
            'order_number' => 'ORD-SYNC-1',
            'status' => 'closed',
            'total' => '3000.00',
            'closed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menu->id,
            'quantity' => 1,
            'unit_price' => '3000.00',
            'status' => 'served',
        ]);

        app(InventoryDeductionService::class)->deductForOrder($order);

        $this->assertFalse($menu->fresh()->is_available);
        $this->assertEquals(0.0, (float) $stock->fresh()->current_stock);
    }
}
