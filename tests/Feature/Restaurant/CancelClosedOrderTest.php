<?php

declare(strict_types=1);

namespace Tests\Feature\Restaurant;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\Inventory\Models\RecipeIngredient;
use App\Domain\Inventory\Models\StockItem;
use App\Domain\Inventory\Services\InventoryDeductionService;
use App\Domain\Restaurant\Services\OrderService;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Models\Outlet;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Till\Models\Payment;
use Tests\TenantTestCase;

class CancelClosedOrderTest extends TenantTestCase
{
    public function test_cancelling_closed_bar_order_restores_beverage_stock(): void
    {
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

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'order_number' => 'ORD-VOID-1',
            'status' => 'closed',
            'total' => '16000.00',
            'closed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
            'unit_price' => '8000.00',
            'status' => 'served',
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'method' => 'card',
            'amount' => '16000.00',
            'status' => 'captured',
            'currency' => 'TZS',
        ]);

        app(InventoryDeductionService::class)->deductForOrder($order);
        $this->assertEquals(8.0, (float) $stock->fresh()->current_stock);

        app(OrderService::class)->cancel($order->fresh());

        $this->assertEquals('voided', $order->fresh()->status);
        $this->assertEquals(10.0, (float) $stock->fresh()->current_stock);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_id' => $order->id,
            'movement_type' => 'consumption',
        ]);
        $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
    }

    public function test_voided_orders_are_excluded_from_sales_report_queries(): void
    {
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
            'cost' => '4000.00',
        ]);

        $closedOrder = Order::query()->create([
            'outlet_id' => $outlet->id,
            'order_number' => 'ORD-KEEP-1',
            'status' => 'closed',
            'total' => '12000.00',
            'closed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $closedOrder->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'unit_price' => '12000.00',
            'status' => 'served',
        ]);

        Payment::query()->create([
            'order_id' => $closedOrder->id,
            'method' => 'cash',
            'amount' => '12000.00',
            'status' => 'captured',
            'currency' => 'TZS',
            'created_at' => now(),
        ]);

        $voidedOrder = Order::query()->create([
            'outlet_id' => $outlet->id,
            'order_number' => 'ORD-VOID-2',
            'status' => 'voided',
            'total' => '24000.00',
            'closed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $voidedOrder->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
            'unit_price' => '12000.00',
            'status' => 'voided',
        ]);

        Payment::query()->create([
            'order_id' => $voidedOrder->id,
            'method' => 'cash',
            'amount' => '24000.00',
            'status' => 'captured',
            'currency' => 'TZS',
            'created_at' => now(),
        ]);

        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $directRevenue = (float) Payment::query()
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->join('outlets', 'orders.outlet_id', '=', 'outlets.id')
            ->whereNull('payments.folio_id')
            ->whereNotNull('payments.order_id')
            ->where('orders.status', 'closed')
            ->whereBetween('payments.created_at', [$from, $to])
            ->whereIn('outlets.type', ['restaurant', 'bar', 'lounge'])
            ->sum('payments.amount');

        $cogs = (float) OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('outlets', 'orders.outlet_id', '=', 'outlets.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->where('orders.status', 'closed')
            ->whereBetween('orders.closed_at', [$from, $to])
            ->whereIn('outlets.type', ['restaurant', 'bar', 'lounge'])
            ->where('order_items.status', '!=', 'voided')
            ->selectRaw('SUM(order_items.quantity * COALESCE(menu_items.cost, 0)) as total')
            ->value('total');

        $this->assertEquals(12000.0, $directRevenue);
        $this->assertEquals(4000.0, $cogs);
    }

    public function test_cancelling_closed_order_voids_folio_charges(): void
    {
        $outlet = Outlet::query()->create([
            'name' => 'Test Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'vacant_clean',
        ]);
        $reservation = Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'status' => 'checked_in',
        ]);

        $folio = app(FolioService::class)->openFolio($reservation);

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'order_number' => 'ORD-FOLIO-1',
            'status' => 'closed',
            'folio_id' => $folio->id,
            'total' => '50000.00',
            'closed_at' => now(),
        ]);

        $folioTxn = FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'restaurant',
            'description' => 'Dinner',
            'amount' => '50000.00',
            'tax_amount' => '0',
            'currency' => 'TZS',
            'reference_id' => $order->id,
            'reference_type' => Order::class,
            'posted_at' => now(),
        ]);

        app(OrderService::class)->cancel($order->fresh());

        $this->assertNotNull($folioTxn->fresh()->voided_at);
        $this->assertEquals('voided', $order->fresh()->status);
    }
}
