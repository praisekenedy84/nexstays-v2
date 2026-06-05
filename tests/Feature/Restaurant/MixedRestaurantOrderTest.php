<?php

declare(strict_types=1);

namespace Tests\Feature\Restaurant;

use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Restaurant\Services\OrderService;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\PaymentMethodSettingsService;
use App\Domain\Shared\Services\ReportingService;
use App\Domain\Till\Models\Payment;
use Brick\Money\Money;
use Tests\TenantTestCase;

class MixedRestaurantOrderTest extends TenantTestCase
{
    public function test_mixed_restaurant_order_posts_split_folio_charges(): void
    {
        $restaurant = Outlet::query()->create([
            'name' => 'Main Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        $bar = Outlet::query()->create([
            'name' => 'Pool Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $foodCategory = $restaurant->menuCategories()->create(['name' => 'Mains', 'display_order' => 1]);
        $foodItem = MenuItem::query()->create([
            'category_id' => $foodCategory->id,
            'name' => 'Grilled Tilapia',
            'price' => '30000.00',
            'is_available' => true,
        ]);

        $drinkCategory = $bar->menuCategories()->create(['name' => 'Beers', 'display_order' => 1]);
        $drinkItem = MenuItem::query()->create([
            'category_id' => $drinkCategory->id,
            'name' => 'Safari Lager',
            'price' => '8000.00',
            'is_available' => true,
        ]);

        $roomType = RoomType::factory()->create();
        $reservation = Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'checked_in',
        ]);
        $folio = app(FolioService::class)->openFolio($reservation);

        $orderService = app(OrderService::class);
        $order = $orderService->create($restaurant, ['covers' => 2, 'table_id' => null], [
            ['menu_item_id' => $foodItem->id, 'quantity' => 1],
            ['menu_item_id' => $drinkItem->id, 'quantity' => 2],
        ]);

        foreach ($order->items as $item) {
            $order = $orderService->updateItemStatus($order, $item, 'sent');
            $order = $orderService->updateItemStatus($order, $item, 'preparing');
            $order = $orderService->updateItemStatus($order, $item, 'ready');
            $orderService->updateItemStatus($order, $item, 'served');
        }

        $orderService->postToFolio($order->fresh(), $folio);

        $this->assertEquals('closed', $order->fresh()->status);
        $this->assertEquals(46000.0, (float) $order->fresh()->total);

        $this->assertDatabaseHas('folio_transactions', [
            'folio_id' => $folio->id,
            'transaction_type' => 'restaurant',
            'amount' => 30000,
            'reference_id' => $order->id,
            'reference_type' => Order::class,
        ]);

        $this->assertDatabaseHas('folio_transactions', [
            'folio_id' => $folio->id,
            'transaction_type' => 'bar',
            'amount' => 16000,
            'reference_id' => $order->id,
            'reference_type' => Order::class,
        ]);
    }

    public function test_mixed_restaurant_order_splits_direct_payment_revenue_in_reporting(): void
    {
        $this->mock(PaymentMethodSettingsService::class, function ($mock): void {
            $mock->shouldReceive('isEnabled')->andReturn(true);
        });

        $restaurant = Outlet::query()->create([
            'name' => 'Main Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        $bar = Outlet::query()->create([
            'name' => 'Pool Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $foodCategory = $restaurant->menuCategories()->create(['name' => 'Mains', 'display_order' => 1]);
        $foodItem = MenuItem::query()->create([
            'category_id' => $foodCategory->id,
            'name' => 'Grilled Tilapia',
            'price' => '30000.00',
            'is_available' => true,
        ]);

        $drinkCategory = $bar->menuCategories()->create(['name' => 'Beers', 'display_order' => 1]);
        $drinkItem = MenuItem::query()->create([
            'category_id' => $drinkCategory->id,
            'name' => 'Safari Lager',
            'price' => '8000.00',
            'is_available' => true,
        ]);

        $orderService = app(OrderService::class);
        $order = $orderService->create($restaurant, ['covers' => 2], [
            ['menu_item_id' => $foodItem->id, 'quantity' => 1],
            ['menu_item_id' => $drinkItem->id, 'quantity' => 2],
        ]);

        foreach ($order->items as $item) {
            $order = $orderService->updateItemStatus($order, $item, 'sent');
            $order = $orderService->updateItemStatus($order, $item, 'preparing');
            $order = $orderService->updateItemStatus($order, $item, 'ready');
            $orderService->updateItemStatus($order, $item, 'served');
        }

        $orderService->recordDirectPayment(
            $order->fresh(),
            'card',
            Money::of('46000', 'TZS'),
        );

        $split = app(ReportingService::class)->fbRevenueSplit(now()->startOfDay(), now()->endOfDay());

        $this->assertEquals('30000', $split['food']);
        $this->assertEquals('16000', $split['drinks']);
        $this->assertEquals('46000', $split['total']);
    }

    public function test_mixed_restaurant_order_settles_as_single_payment(): void
    {
        $this->mock(PaymentMethodSettingsService::class, function ($mock): void {
            $mock->shouldReceive('isEnabled')->andReturn(true);
        });

        $restaurant = Outlet::query()->create([
            'name' => 'Main Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);

        $bar = Outlet::query()->create([
            'name' => 'Pool Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $foodCategory = $restaurant->menuCategories()->create(['name' => 'Mains', 'display_order' => 1]);
        $foodItem = MenuItem::query()->create([
            'category_id' => $foodCategory->id,
            'name' => 'Grilled Tilapia',
            'price' => '30000.00',
            'is_available' => true,
        ]);

        $drinkCategory = $bar->menuCategories()->create(['name' => 'Beers', 'display_order' => 1]);
        $drinkItem = MenuItem::query()->create([
            'category_id' => $drinkCategory->id,
            'name' => 'Safari Lager',
            'price' => '8000.00',
            'is_available' => true,
        ]);

        $orderService = app(OrderService::class);
        $order = $orderService->create($restaurant, ['covers' => 2], [
            ['menu_item_id' => $foodItem->id, 'quantity' => 1],
            ['menu_item_id' => $drinkItem->id, 'quantity' => 1],
        ]);

        foreach ($order->items as $item) {
            $order = $orderService->updateItemStatus($order, $item, 'sent');
            $order = $orderService->updateItemStatus($order, $item, 'preparing');
            $order = $orderService->updateItemStatus($order, $item, 'ready');
            $orderService->updateItemStatus($order, $item, 'served');
        }

        $orderService->recordDirectPayment(
            $order->fresh(),
            'card',
            Money::of('38000', 'TZS'),
        );

        $this->assertEquals(1, Payment::query()->where('order_id', $order->id)->count());
        $this->assertEquals(38000.0, (float) Payment::query()->where('order_id', $order->id)->value('amount'));
        $this->assertEquals(2, $order->fresh()->items()->where('status', '!=', 'voided')->count());
    }
}
