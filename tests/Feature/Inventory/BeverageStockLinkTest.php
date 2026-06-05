<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\Models\StockItem;
use App\Domain\Inventory\Services\BeverageStockLinkService;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Outlet;
use Tests\TenantTestCase;

class BeverageStockLinkTest extends TenantTestCase
{
    public function test_menu_register_awaiting_creates_linked_stock(): void
    {
        $bar = Outlet::query()->create(['name' => 'Bar', 'type' => 'bar', 'is_active' => true]);
        $cat = $bar->menuCategories()->create(['name' => 'Spirits', 'display_order' => 1]);
        $menu = MenuItem::query()->create([
            'category_id' => $cat->id,
            'name' => 'Safari Lager',
            'price' => '5000.00',
        ]);

        app(BeverageStockLinkService::class)->syncMenuInventory($menu, null, true, 1, 'bottle');

        $menu->load('linkedStockItem', 'recipeIngredients');
        $this->assertTrue($menu->linkedStockItem->awaiting_stock);
        $this->assertEquals(0.0, (float) $menu->linkedStockItem->current_stock);
        $this->assertCount(1, $menu->recipeIngredients);
    }

    public function test_stock_form_link_connects_menu_item(): void
    {
        $bar = Outlet::query()->create(['name' => 'Bar', 'type' => 'bar', 'is_active' => true]);
        $cat = $bar->menuCategories()->create(['name' => 'Spirits', 'display_order' => 1]);
        $menu = MenuItem::query()->create([
            'category_id' => $cat->id,
            'name' => 'Gin & Tonic',
            'price' => '12000.00',
        ]);

        $stock = StockItem::query()->create([
            'outlet_id' => $bar->id,
            'name' => 'Gin bottle',
            'unit' => 'bottle',
            'current_stock' => 24,
            'reorder_level' => 6,
            'category' => 'beverage',
        ]);

        app(BeverageStockLinkService::class)->syncStockMenuLink($stock, $menu->id, 1, 'bottle');

        $menu->load('recipeIngredients');
        $this->assertEquals($stock->id, $menu->recipeIngredients->first()->stock_item_id);
        $this->assertEquals($menu->id, $stock->fresh()->menu_item_id);
    }
}
