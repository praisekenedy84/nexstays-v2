<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\Actions\RestockStockItem;
use App\Domain\Inventory\Models\StockItem;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Purchases\Actions\ReceivePurchaseOrder;
use App\Domain\Purchases\Models\PurchaseOrder;
use App\Domain\Purchases\Models\PurchaseOrderLine;
use App\Domain\Shared\Models\Outlet;
use Tests\TenantTestCase;

class StockHistoryTest extends TenantTestCase
{
    public function test_item_history_page_shows_restock_accountability(): void
    {
        $bar = Outlet::query()->create(['name' => 'Bar', 'type' => 'bar', 'is_active' => true]);

        $stock = StockItem::query()->create([
            'outlet_id' => $bar->id,
            'name' => 'Gin (750ml)',
            'unit' => 'ml',
            'current_stock' => 5,
            'reorder_level' => 10,
            'awaiting_stock' => true,
        ]);

        app(RestockStockItem::class)->execute($stock, 20, 'Weekly delivery');

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.stock-items.history', $stock));

        $response->assertOk();
        $response->assertSee('Stock history');
        $response->assertSee('Gin (750ml)');
        $response->assertSee('Weekly delivery');
        $response->assertSee($this->user->name);
        $response->assertSee('+20');
        $response->assertSee('Restock');
    }

    public function test_global_movements_page_lists_restocks(): void
    {
        $bar = Outlet::query()->create(['name' => 'Bar', 'type' => 'bar', 'is_active' => true]);

        $stock = StockItem::query()->create([
            'outlet_id' => $bar->id,
            'name' => 'Vodka',
            'unit' => 'bottle',
            'current_stock' => 0,
            'reorder_level' => 2,
            'awaiting_stock' => true,
        ]);

        app(RestockStockItem::class)->execute($stock, 12, 'Opening stock');

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.stock-items.movements', [
                'type' => 'restock',
                'search' => 'Vodka',
            ]));

        $response->assertOk();
        $response->assertSee('Vodka');
        $response->assertSee('Opening stock');
        $response->assertSee($this->user->name);
    }

    public function test_purchase_receive_updates_last_restocked_audit(): void
    {
        $bar = Outlet::query()->create(['name' => 'Bar', 'type' => 'bar', 'is_active' => true]);

        $stock = StockItem::query()->create([
            'outlet_id' => $bar->id,
            'name' => 'Whisky',
            'unit' => 'bottle',
            'current_stock' => 1,
            'reorder_level' => 2,
            'awaiting_stock' => false,
        ]);

        $po = PurchaseOrder::query()->create([
            'outlet_id' => $bar->id,
            'po_number' => 'PO-TEST-1',
            'department' => 'bar',
            'supplier_name' => 'Test Supplier',
            'status' => 'ordered',
            'created_by' => $this->user->id,
        ]);

        $line = PurchaseOrderLine::query()->create([
            'purchase_order_id' => $po->id,
            'stock_item_id' => $stock->id,
            'quantity' => 6,
            'unit_cost' => 25000,
            'line_total' => 150000,
        ]);

        app(ReceivePurchaseOrder::class)->execute($po, [
            [
                'line_id' => $line->id,
                'qty_received' => 6,
                'actual_unit_cost' => 25000,
            ],
        ]);

        $stock->refresh();

        $this->assertEquals(7.0, (float) $stock->current_stock);
        $this->assertNotNull($stock->last_restocked_at);
        $this->assertEquals($this->user->id, $stock->last_restocked_by);

        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $stock->id,
            'movement_type' => 'purchase',
            'quantity' => 6,
            'performed_by' => $this->user->id,
        ]);
    }

    public function test_history_filter_by_type(): void
    {
        $bar = Outlet::query()->create(['name' => 'Bar', 'type' => 'bar', 'is_active' => true]);

        $stock = StockItem::query()->create([
            'outlet_id' => $bar->id,
            'name' => 'Rum',
            'unit' => 'bottle',
            'current_stock' => 10,
            'reorder_level' => 2,
        ]);

        StockMovement::query()->create([
            'stock_item_id' => $stock->id,
            'movement_type' => 'restock',
            'quantity' => 5,
            'performed_by' => $this->user->id,
            'notes' => 'Manual restock note',
            'created_at' => now(),
        ]);

        StockMovement::query()->create([
            'stock_item_id' => $stock->id,
            'movement_type' => 'consumption',
            'quantity' => -2,
            'performed_by' => $this->user->id,
            'notes' => 'Order use',
            'created_at' => now(),
        ]);

        $restocksOnly = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.stock-items.history', ['stockItem' => $stock, 'type' => 'restock']));

        $restocksOnly->assertOk();
        $restocksOnly->assertSee('Manual restock note');
        $restocksOnly->assertDontSee('Order use');
    }

    public function test_global_movements_filter_switches_away_from_restock(): void
    {
        $bar = Outlet::query()->create(['name' => 'Bar', 'type' => 'bar', 'is_active' => true]);

        $stock = StockItem::query()->create([
            'outlet_id' => $bar->id,
            'name' => 'Tequila',
            'unit' => 'bottle',
            'current_stock' => 8,
            'reorder_level' => 2,
        ]);

        StockMovement::query()->create([
            'stock_item_id' => $stock->id,
            'movement_type' => 'restock',
            'quantity' => 4,
            'performed_by' => $this->user->id,
            'notes' => 'Restock only note',
            'created_at' => now(),
        ]);

        StockMovement::query()->create([
            'stock_item_id' => $stock->id,
            'movement_type' => 'consumption',
            'quantity' => -1,
            'performed_by' => $this->user->id,
            'notes' => 'Consumption only note',
            'created_at' => now(),
        ]);

        $consumption = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.stock-items.movements', [
                'type' => 'consumption',
                'search' => 'Tequila',
            ]));

        $consumption->assertOk();
        $consumption->assertSee('Consumption only note');
        $consumption->assertDontSee('Restock only note');
        $consumption->assertSee('value="consumption"', false);
        $consumption->assertSee('selected', false);
    }
}
