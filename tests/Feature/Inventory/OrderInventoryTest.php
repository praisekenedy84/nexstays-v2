<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\RecipeIngredient;
use App\Domain\Inventory\Models\StockItem;
use App\Domain\Inventory\Services\InventoryDeductionService;
use App\Domain\Restaurant\Services\OrderService;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Services\PaymentMethodSettingsService;
use Brick\Money\Money;
use Tests\TenantTestCase;

class OrderInventoryTest extends TenantTestCase
{
    public function test_add_items_rejects_when_recipe_stock_insufficient(): void
    {
        $this->user->givePermissionTo(['manage-orders', 'view-orders']);

        $outlet = Outlet::query()->create([
            'name' => 'Test Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $category = $outlet->menuCategories()->create(['name' => 'Drinks', 'display_order' => 1]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Gin Tonic',
            'price' => '12000.00',
            'is_available' => true,
        ]);

        $stock = StockItem::query()->create([
            'outlet_id' => $outlet->id,
            'name' => 'Gin (750ml)',
            'unit' => 'ml',
            'current_stock' => 30,
            'reorder_level' => 10,
        ]);

        RecipeIngredient::query()->create([
            'menu_item_id' => $menuItem->id,
            'stock_item_id' => $stock->id,
            'quantity' => 45,
            'unit' => 'ml',
        ]);

        $order = app(OrderService::class)->create($outlet, ['guest_label' => 'Walk-in']);

        $this->expectException(InsufficientStockException::class);

        app(OrderService::class)->addItems($order, [
            ['menu_item_id' => $menuItem->id, 'quantity' => 1],
        ]);
    }

    public function test_settling_bar_order_depletes_stock_via_order_closed_listener(): void
    {
        $this->mock(PaymentMethodSettingsService::class, function ($mock): void {
            $mock->shouldReceive('isEnabled')->andReturn(true);
        });

        $outlet = Outlet::query()->create([
            'name' => 'Test Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $category = $outlet->menuCategories()->create(['name' => 'Drinks', 'display_order' => 1]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Safari Lager',
            'price' => '8000.00',
        ]);

        $stock = StockItem::query()->create([
            'outlet_id' => $outlet->id,
            'name' => 'Safari Lager (bottle)',
            'unit' => 'bottle',
            'current_stock' => 10,
            'reorder_level' => 2,
        ]);

        RecipeIngredient::query()->create([
            'menu_item_id' => $menuItem->id,
            'stock_item_id' => $stock->id,
            'quantity' => 1,
            'unit' => 'bottle',
        ]);

        $orderService = app(OrderService::class);
        $order = $orderService->create($outlet, ['guest_label' => 'Walk-in'], [
            ['menu_item_id' => $menuItem->id, 'quantity' => 2],
        ]);

        $item = $order->items->first();
        $order = $orderService->updateItemStatus($order, $item, 'sent');
        $order = $orderService->updateItemStatus($order, $order->items->first(), 'preparing');
        $order = $orderService->updateItemStatus($order, $order->items->first(), 'ready');
        $orderService->updateItemStatus($order, $order->items->first(), 'served');

        $orderService->recordDirectPayment(
            $order->fresh(),
            'card',
            Money::of('16000', 'TZS'),
        );

        $this->assertEquals(8.0, (float) $stock->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $stock->id,
            'movement_type' => 'consumption',
            'reference_id' => $order->id,
        ]);
    }

    public function test_deduct_for_closed_order_reduces_stock_from_recipe(): void
    {
        $outlet = Outlet::query()->create([
            'name' => 'Test Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $category = $outlet->menuCategories()->create(['name' => 'Drinks', 'display_order' => 1]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Dawa',
            'price' => '15000.00',
        ]);

        $stock = StockItem::query()->create([
            'outlet_id' => $outlet->id,
            'name' => 'Gin (750ml)',
            'unit' => 'ml',
            'current_stock' => 500,
            'reorder_level' => 100,
        ]);

        RecipeIngredient::query()->create([
            'menu_item_id' => $menuItem->id,
            'stock_item_id' => $stock->id,
            'quantity' => 45,
            'unit' => 'ml',
        ]);

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'order_number' => 'ORD-INV-1',
            'status' => 'closed',
            'waiter_id' => $this->user->id,
            'total' => '15000.00',
            'closed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
            'unit_price' => '15000.00',
            'status' => 'served',
        ]);

        app(InventoryDeductionService::class)->deductForOrder($order);

        $this->assertEquals(410.0, (float) $stock->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $stock->id,
            'movement_type' => 'consumption',
            'reference_id' => $order->id,
        ]);
    }

    public function test_restaurant_orders_ignore_recipes_even_when_defined(): void
    {
        $this->user->givePermissionTo(['manage-orders', 'view-orders']);

        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        $category = $outlet->menuCategories()->create(['name' => 'Mains', 'display_order' => 1]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Grilled Fish',
            'price' => '25000.00',
        ]);

        $stock = StockItem::query()->create([
            'outlet_id' => $outlet->id,
            'name' => 'Fish fillet',
            'unit' => 'kg',
            'current_stock' => 0,
            'reorder_level' => 1,
        ]);

        RecipeIngredient::query()->create([
            'menu_item_id' => $menuItem->id,
            'stock_item_id' => $stock->id,
            'quantity' => 0.3,
            'unit' => 'kg',
        ]);

        $order = app(OrderService::class)->create($outlet, ['covers' => 2]);

        app(OrderService::class)->addItems($order, [
            ['menu_item_id' => $menuItem->id, 'quantity' => 5],
        ]);

        $closed = Order::query()->create([
            'outlet_id' => $outlet->id,
            'order_number' => 'ORD-REST-1',
            'status' => 'closed',
            'total' => '125000.00',
            'closed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $closed->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 5,
            'unit_price' => '25000.00',
            'status' => 'served',
        ]);

        app(InventoryDeductionService::class)->deductForOrder($closed);

        $this->assertEquals(0.0, (float) $stock->fresh()->current_stock);
        $this->assertDatabaseMissing('stock_movements', [
            'stock_item_id' => $stock->id,
            'movement_type' => 'consumption',
        ]);
    }
}
