<?php

declare(strict_types=1);

namespace Tests\Feature\Restaurant;

use App\Domain\Restaurant\Services\OrderService;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Outlet;
use Tests\TenantTestCase;

class OrderApiTest extends TenantTestCase
{
    public function test_it_creates_order_with_items(): void
    {
        $this->user->givePermissionTo(['manage-orders', 'view-orders']);

        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        $category = $outlet->menuCategories()->create(['name' => 'Test', 'display_order' => 1]);
        $item = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Test Dish',
            'price' => '15000.00',
        ]);

        $response = $this->tenantJson('POST', "/api/v1/outlets/{$outlet->id}/orders", [
            'covers' => 2,
            'items' => [
                ['menu_item_id' => $item->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated();
        $this->assertEquals('30000.00', $response->json('data.total'));
    }

    public function test_voiding_item_recalculates_order_total(): void
    {
        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        $category = $outlet->menuCategories()->create(['name' => 'Test', 'display_order' => 1]);
        $itemA = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Dish A',
            'price' => '15000.00',
        ]);
        $itemB = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Dish B',
            'price' => '10000.00',
        ]);

        $orderService = app(OrderService::class);
        $order = $orderService->create($outlet, ['covers' => 1], [
            ['menu_item_id' => $itemA->id, 'quantity' => 2],
            ['menu_item_id' => $itemB->id, 'quantity' => 1],
        ]);

        $this->assertEquals(40000.0, (float) $order->total);

        $itemToVoid = $order->items->firstWhere('menu_item_id', $itemA->id);
        $order = $orderService->updateItemStatus($order, $itemToVoid, 'voided', 'test_void');

        $this->assertEquals(10000.0, (float) $order->total);
        $this->assertEquals('voided', $itemToVoid->fresh()->status);
    }
}
