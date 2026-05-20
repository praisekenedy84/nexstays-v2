<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Inventory\Models\StockItem;
use App\Domain\Purchases\Actions\CreatePurchaseOrder;
use App\Domain\Purchases\Models\PurchaseOrder;
use App\Domain\Shared\Models\Outlet;
use Tests\TenantTestCase;

class PurchaseOrderTest extends TenantTestCase
{
    public function test_it_receives_purchase_and_increments_stock(): void
    {
        $this->actingAs($this->user, 'web');

        $outlet = Outlet::query()->first();
        $stockItem = StockItem::query()->create([
            'outlet_id' => $outlet?->id,
            'name' => 'Test flour',
            'unit' => 'kg',
            'current_stock' => 10,
            'reorder_level' => 5,
        ]);

        $order = app(CreatePurchaseOrder::class)->execute([
            'department' => 'kitchen',
            'supplier_name' => 'Test Supplier',
            'lines' => [
                ['stock_item_id' => $stockItem->id, 'quantity' => 5, 'unit_cost' => 2000],
            ],
        ], receiveImmediately: true);

        $this->assertSame('received', $order->status);
        $this->assertEquals(15.0, (float) $stockItem->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $stockItem->id,
            'movement_type' => 'purchase',
            'reference_type' => PurchaseOrder::class,
        ]);
    }
}
