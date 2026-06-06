<?php

declare(strict_types=1);

namespace Tests\Feature\Restaurant;

use App\Domain\Restaurant\Actions\DeleteOrder;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Models\Outlet;
use Tests\TenantTestCase;

class DeleteOrderTest extends TenantTestCase
{
    public function test_it_permanently_deletes_voided_order(): void
    {
        $outlet = Outlet::query()->create([
            'name' => 'Test Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $category = $outlet->menuCategories()->create(['name' => 'Drinks', 'display_order' => 1]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Soda',
            'price' => '3000.00',
        ]);

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'order_number' => 'ORD-DEL-1',
            'status' => 'voided',
            'total' => '3000.00',
            'closed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'unit_price' => '3000.00',
            'status' => 'voided',
        ]);

        app(DeleteOrder::class)->execute($order);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
    }

    public function test_it_blocks_deleting_closed_orders(): void
    {
        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'order_number' => 'ORD-CLOSED-DEL',
            'status' => 'closed',
            'total' => '5000.00',
            'closed_at' => now(),
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Void the order before permanently deleting it.');

        app(DeleteOrder::class)->execute($order);
    }

    public function test_it_blocks_deleting_open_orders(): void
    {
        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'order_number' => 'ORD-OPEN-1',
            'status' => 'open',
            'total' => '0',
        ]);

        $this->expectException(\DomainException::class);

        app(DeleteOrder::class)->execute($order);
    }
}
