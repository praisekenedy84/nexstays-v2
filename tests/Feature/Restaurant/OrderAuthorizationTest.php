<?php

declare(strict_types=1);

namespace Tests\Feature\Restaurant;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\Outlet;
use App\Models\User;
use Tests\TenantTestCase;

class OrderAuthorizationTest extends TenantTestCase
{
    public function test_waiter_cannot_delete_another_staff_order(): void
    {
        $this->user->syncRoles([]);
        $this->user->givePermissionTo(['view-orders', 'manage-own-orders']);

        $otherWaiter = User::factory()->create();

        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $otherWaiter->id,
            'order_number' => 'ORD-OTHER-1',
            'status' => 'voided',
            'total' => '5000.00',
            'closed_at' => now(),
        ]);

        $this->web()
            ->actingAs($this->user, 'web')
            ->delete(route('tenant.fb.orders.destroy', $order))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_waiter_cannot_delete_own_voided_order(): void
    {
        $this->user->syncRoles([]);
        $this->user->givePermissionTo(['view-orders', 'manage-own-orders']);

        $outlet = Outlet::query()->create([
            'name' => 'Test Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'ORD-MINE-1',
            'status' => 'voided',
            'total' => '5000.00',
            'closed_at' => now(),
        ]);

        $this->web()
            ->actingAs($this->user, 'web')
            ->delete(route('tenant.fb.orders.destroy', $order))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_waiter_cannot_delete_own_closed_order(): void
    {
        $this->user->syncRoles([]);
        $this->user->givePermissionTo(['view-orders', 'manage-own-orders']);

        $outlet = Outlet::query()->create([
            'name' => 'Test Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'ORD-CLOSED-1',
            'status' => 'closed',
            'total' => '5000.00',
            'closed_at' => now(),
        ]);

        $this->web()
            ->actingAs($this->user, 'web')
            ->delete(route('tenant.fb.orders.destroy', $order))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'closed']);
    }

    public function test_manager_can_delete_voided_order(): void
    {
        $outlet = Outlet::query()->create([
            'name' => 'Test Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'ORD-MGR-DEL-1',
            'status' => 'voided',
            'total' => '5000.00',
            'closed_at' => now(),
        ]);

        $this->web()
            ->actingAs($this->user, 'web')
            ->delete(route('tenant.fb.orders.destroy', $order))
            ->assertRedirect();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_voided_order_appears_in_staff_sales_list_but_not_totals(): void
    {
        $this->user->syncRoles([]);
        $this->user->givePermissionTo(['view-orders', 'manage-own-orders']);

        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'ORD-VOID-LIST',
            'status' => 'voided',
            'total' => '9000.00',
            'closed_at' => now(),
        ]);

        Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'ORD-CLOSED-LIST',
            'status' => 'closed',
            'total' => '12000.00',
            'closed_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.fb.orders.index'));

        $response->assertOk();
        $response->assertSee('ORD-VOID-LIST');
        $response->assertSee('ORD-CLOSED-LIST');
        $response->assertSee('12,000');
        $response->assertDontSee('21,000');
    }

    public function test_sales_list_shows_only_own_orders_without_view_all_permission(): void
    {
        $this->user->syncRoles([]);
        $this->user->givePermissionTo(['view-orders', 'manage-own-orders']);

        $otherWaiter = User::factory()->create();

        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $otherWaiter->id,
            'order_number' => 'ORD-OTHER-2',
            'status' => 'closed',
            'total' => '10000.00',
            'closed_at' => now(),
        ]);

        $ownOrder = Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'ORD-MINE-2',
            'status' => 'closed',
            'total' => '15000.00',
            'closed_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.fb.orders.index'));

        $response->assertOk();
        $response->assertSee($ownOrder->order_number);
        $response->assertDontSee('ORD-OTHER-2');
    }

    public function test_sales_summary_is_scoped_to_own_orders_for_staff(): void
    {
        $this->user->syncRoles([]);
        $this->user->givePermissionTo(['view-orders', 'manage-own-orders']);

        $otherWaiter = User::factory()->create();

        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $otherWaiter->id,
            'order_number' => 'ORD-OTHER-SUM',
            'status' => 'closed',
            'total' => '50000.00',
            'closed_at' => now(),
        ]);

        Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'ORD-MINE-SUM',
            'status' => 'closed',
            'total' => '12000.00',
            'closed_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.fb.orders.index'));

        $response->assertOk();
        $response->assertSee('12,000');
        $response->assertDontSee('50,000');
        $response->assertSee('Your total sales');
    }

    public function test_finance_role_sees_all_staff_sales_and_totals(): void
    {
        $this->user->syncRoles([]);
        $this->user->givePermissionTo(['view-orders', 'view-all-orders']);

        $otherWaiter = User::factory()->create(['name' => 'Other Waiter']);

        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $otherWaiter->id,
            'order_number' => 'ORD-FIN-OTHER',
            'status' => 'closed',
            'total' => '25000.00',
            'closed_at' => now(),
        ]);

        Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'ORD-FIN-MINE',
            'status' => 'closed',
            'total' => '8000.00',
            'closed_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.fb.orders.index'));

        $response->assertOk();
        $response->assertSee('ORD-FIN-OTHER');
        $response->assertSee('ORD-FIN-MINE');
        $response->assertSee('Other Waiter');
        $response->assertSee('25,000');
        $response->assertSee('8,000');
        $response->assertSee('Total sales');
    }

    public function test_manager_with_view_all_sees_everyones_sales(): void
    {
        $otherWaiter = User::factory()->create();

        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $otherWaiter->id,
            'order_number' => 'ORD-OTHER-3',
            'status' => 'closed',
            'total' => '10000.00',
            'closed_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.fb.orders.index'));

        $response->assertOk();
        $response->assertSee('ORD-OTHER-3');
    }
}
