<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Shared\Models\MenuCategory;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Services\ReportingService;
use Tests\TenantTestCase;

class MenuItemSalesSummaryReportTest extends TenantTestCase
{
    public function test_menu_item_sales_summary_groups_items_by_category(): void
    {
        $outlet = Outlet::query()->create([
            'name' => 'Main Restaurant',
            'type' => 'restaurant',
            'is_active' => true,
        ]);
        $category = MenuCategory::query()->create([
            'outlet_id' => $outlet->id,
            'name' => 'Main Courses',
            'display_order' => 1,
            'is_active' => true,
        ]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Grilled Tilapia',
            'price' => '18000.00',
            'cost' => '7000.00',
            'is_available' => true,
        ]);

        $order = Order::query()->create([
            'outlet_id' => $outlet->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'ORD-MISS-1',
            'status' => 'closed',
            'subtotal' => 15254.24,
            'tax_amount' => 2745.76,
            'discount_amount' => 0,
            'total' => 18000,
            'closed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
            'unit_price' => 9000,
            'status' => 'served',
        ]);

        $report = app(ReportingService::class)->menuItemSalesSummary(
            now()->startOfDay(),
            now()->endOfDay(),
            $outlet->id,
            null,
        );

        $this->assertCount(1, $report['categories']);
        $this->assertSame('Food', $report['categories'][0]['name']);
        $this->assertSame('Main Courses', $report['categories'][0]['subcategories'][0]['name']);
        $this->assertSame('Grilled Tilapia', $report['categories'][0]['subcategories'][0]['items'][0]['name']);
        $this->assertSame(2, $report['categories'][0]['subcategories'][0]['items'][0]['quantity']);
        $this->assertEqualsWithDelta(18000, $report['grand_total']['total_amount'], 0.01);
    }

    public function test_menu_item_sales_summary_page_loads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.menu-item-sales-summary'));

        $response->assertOk();
        $response->assertSee('Menu item sales summary');
    }

    public function test_menu_item_sales_summary_excel_export_downloads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.menu-item-sales-summary.export-excel', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->assertStringContainsString('Menu Item Sales Summary', $response->streamedContent());
    }

    public function test_menu_item_sales_summary_pdf_export_downloads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.menu-item-sales-summary.export-pdf', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
