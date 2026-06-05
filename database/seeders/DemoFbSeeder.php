<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Bar\Models\BarTab;
use App\Domain\Inventory\Models\RecipeIngredient;
use App\Domain\Inventory\Models\StockItem;
use App\Domain\Shared\Models\MenuCategory;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Models\OutletTable;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoFbSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Outlet::query()->updateOrCreate(
            ['name' => 'Ocean Grill'],
            ['type' => 'restaurant', 'is_active' => true]
        );

        $bar = Outlet::query()->updateOrCreate(
            ['name' => 'Sunset Bar'],
            ['type' => 'bar', 'is_active' => true]
        );

        $lounge = Outlet::query()->updateOrCreate(
            ['name' => 'Pool Lounge'],
            ['type' => 'lounge', 'is_active' => true]
        );

        foreach (['T1', 'T2', 'T3', 'T4'] as $i => $num) {
            OutletTable::query()->updateOrCreate(
                ['outlet_id' => $restaurant->id, 'table_number' => $num],
                ['capacity' => 4, 'section' => 'Terrace', 'status' => $i === 0 ? 'occupied' : 'available']
            );
        }

        $mains = MenuCategory::query()->updateOrCreate(
            ['outlet_id' => $restaurant->id, 'name' => 'Mains'],
            ['display_order' => 1]
        );

        $grilledFish = MenuItem::withTrashed()->updateOrCreate(
            ['category_id' => $mains->id, 'name' => 'Grilled Red Snapper'],
            [
                'description' => 'Catch of the day with ugali',
                'price' => '45000.00',
                'cost' => '18000.00',
                'tags' => ['signature'],
                'is_available' => true,
            ]
        );
        if ($grilledFish->trashed()) {
            $grilledFish->restore();
        }

        $chickenPilau = MenuItem::withTrashed()->updateOrCreate(
            ['category_id' => $mains->id, 'name' => 'Chicken Pilau'],
            ['price' => '28000.00', 'is_available' => true]
        );
        if ($chickenPilau->trashed()) {
            $chickenPilau->restore();
        }

        $barCat = MenuCategory::query()->updateOrCreate(
            ['outlet_id' => $bar->id, 'name' => 'Cocktails'],
            ['display_order' => 1]
        );

        $dawa = MenuItem::withTrashed()->updateOrCreate(
            ['category_id' => $barCat->id, 'name' => 'Dawa'],
            ['price' => '12000.00', 'is_available' => true]
        );
        if ($dawa->trashed()) {
            $dawa->restore();
        }

        $kili = MenuItem::withTrashed()->updateOrCreate(
            ['category_id' => $barCat->id, 'name' => 'Kilimanjaro Lager'],
            ['price' => '8000.00', 'is_available' => true]
        );
        if ($kili->trashed()) {
            $kili->restore();
        }

        $gin = StockItem::query()->updateOrCreate(
            ['name' => 'Gin (750ml)'],
            [
                'outlet_id' => $bar->id,
                'category' => 'beverage',
                'unit' => 'ml',
                'current_stock' => 4500,
                'reorder_level' => 1000,
                'cost_per_unit' => '0.15',
            ]
        );

        RecipeIngredient::query()->updateOrCreate(
            ['menu_item_id' => $dawa->id, 'stock_item_id' => $gin->id],
            ['quantity' => 45, 'unit' => 'ml']
        );

        $lagerStock = StockItem::query()->updateOrCreate(
            ['name' => 'Kilimanjaro Lager (bottle)'],
            [
                'outlet_id' => $bar->id,
                'category' => 'beverage',
                'unit' => 'bottle',
                'current_stock' => 48,
                'reorder_level' => 12,
                'cost_per_unit' => '2500',
            ]
        );

        RecipeIngredient::query()->updateOrCreate(
            ['menu_item_id' => $kili->id, 'stock_item_id' => $lagerStock->id],
            ['quantity' => 1, 'unit' => 'bottle']
        );

        $waiter = User::query()->where('email', config('nexstay.demo.front_desk_email'))->first();

        $table = OutletTable::query()->where('outlet_id', $restaurant->id)->where('table_number', 'T1')->first();

        Order::query()->updateOrCreate(
            ['order_number' => 'ORD-DEMO-OPEN'],
            [
                'outlet_id' => $restaurant->id,
                'table_id' => $table?->id,
                'status' => 'open',
                'covers' => 2,
                'waiter_id' => $waiter?->id,
                'subtotal' => '45000.00',
                'total' => '45000.00',
            ]
        );

        $barOrder = Order::query()->updateOrCreate(
            ['order_number' => 'ORD-DEMO-BAR'],
            [
                'outlet_id' => $bar->id,
                'status' => 'open',
                'guest_label' => 'Walk-in Guest',
                'waiter_id' => $waiter?->id,
                'subtotal' => '20000.00',
                'total' => '20000.00',
            ]
        );

        BarTab::query()->updateOrCreate(
            ['guest_label' => 'Walk-in Guest', 'outlet_id' => $bar->id],
            [
                'order_id' => $barOrder->id,
                'status' => 'open',
                'opened_by' => $waiter?->id ?? User::query()->first()?->id,
            ]
        );

        unset($lounge, $grilledFish, $chickenPilau, $kili);
    }
}
