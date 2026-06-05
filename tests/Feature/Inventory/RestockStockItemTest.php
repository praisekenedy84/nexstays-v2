<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\Actions\RestockStockItem;
use App\Domain\Inventory\Models\StockItem;
use App\Domain\Shared\Models\Outlet;
use Tests\TenantTestCase;

class RestockStockItemTest extends TenantTestCase
{
    public function test_restock_increases_stock_and_records_audit(): void
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

        $updated = app(RestockStockItem::class)->execute($stock, 20, 'Weekly delivery');

        $this->assertEquals(25.0, (float) $updated->current_stock);
        $this->assertFalse($updated->awaiting_stock);
        $this->assertNotNull($updated->last_restocked_at);
        $this->assertEquals($this->user->id, $updated->last_restocked_by);

        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $stock->id,
            'movement_type' => 'restock',
            'quantity' => 20,
            'performed_by' => $this->user->id,
        ]);
    }
}
