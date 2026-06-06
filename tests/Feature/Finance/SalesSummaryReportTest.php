<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Models\MenuCategory;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Services\DivisionSalesService;
use App\Domain\Shared\Services\ReportingService;
use App\Domain\Shared\Services\FolioService;
use Tests\TenantTestCase;

class SalesSummaryReportTest extends TenantTestCase
{
    public function test_sales_summary_report_page_loads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.sales-summary', [
                'period' => 'daily',
                'date' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertSee('Sales summary');
        $response->assertSee('Division breakdown');
    }

    public function test_weekly_sales_summary_includes_daily_breakdown_rows(): void
    {
        $report = app(DivisionSalesService::class)->salesSummaryReport('weekly', now());

        $this->assertSame('weekly', $report['period']);
        $this->assertNotEmpty($report['daily_rows']);
        $this->assertEqualsWithDelta(
            array_sum(array_column($report['daily_rows'], 'total')),
            $report['summary']['total'],
            0.01
        );
    }

    public function test_sales_summary_excel_export_downloads(): void
    {
        $reservation = Reservation::factory()->create(['room_type_id' => RoomType::factory()->create()->id]);
        $folio = app(FolioService::class)->openFolio($reservation);

        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'restaurant',
            'description' => 'Lunch',
            'amount' => 45000,
            'tax_amount' => 8100,
            'posted_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.sales-summary.export-excel', [
                'period' => 'daily',
                'date' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertHeader('content-disposition');

        $excel = $response->streamedContent();
        $this->assertStringContainsString('Sales Summary', $excel);
        $this->assertStringContainsString('Restaurant', $excel);
        $this->assertStringContainsString('45,000', $excel);
    }

    public function test_sales_summary_pdf_export_downloads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.sales-summary.export-pdf', [
                'period' => 'monthly',
                'date' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
    }

    public function test_bar_item_sales_summary_report_page_loads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.bar-sales-summary'));

        $response->assertOk();
        $response->assertSee('Bar item sales summary');
    }

    public function test_outlet_type_item_sales_summary_scopes_to_bar_orders(): void
    {
        $bar = Outlet::query()->create([
            'name' => 'Main Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $lounge = Outlet::query()->create([
            'name' => 'Pool Lounge',
            'type' => 'lounge',
            'is_active' => true,
        ]);

        $barCategory = MenuCategory::query()->create([
            'outlet_id' => $bar->id,
            'name' => 'Cocktails',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $loungeCategory = MenuCategory::query()->create([
            'outlet_id' => $lounge->id,
            'name' => 'Premium spirits',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $barItem = MenuItem::query()->create([
            'category_id' => $barCategory->id,
            'name' => 'Gin & Tonic',
            'price' => '12000.00',
            'cost' => '3000.00',
            'is_available' => true,
        ]);

        $loungeItem = MenuItem::query()->create([
            'category_id' => $loungeCategory->id,
            'name' => 'Old Fashioned',
            'price' => '18000.00',
            'cost' => '5000.00',
            'is_available' => true,
        ]);

        $barOrder = Order::query()->create([
            'outlet_id' => $bar->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'BAR-001',
            'status' => 'closed',
            'subtotal' => 10169.49,
            'tax_amount' => 1830.51,
            'discount_amount' => 0,
            'total' => 12000,
            'closed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $barOrder->id,
            'menu_item_id' => $barItem->id,
            'quantity' => 1,
            'unit_price' => 12000,
            'status' => 'served',
        ]);

        $loungeOrder = Order::query()->create([
            'outlet_id' => $lounge->id,
            'waiter_id' => $this->user->id,
            'order_number' => 'LOUNGE-001',
            'status' => 'closed',
            'subtotal' => 15254.24,
            'tax_amount' => 2745.76,
            'discount_amount' => 0,
            'total' => 18000,
            'closed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $loungeOrder->id,
            'menu_item_id' => $loungeItem->id,
            'quantity' => 1,
            'unit_price' => 18000,
            'status' => 'served',
        ]);

        $barReport = app(ReportingService::class)->menuItemSalesSummary(
            now()->startOfDay(),
            now()->endOfDay(),
            null,
            null,
            'bar',
        );

        $loungeReport = app(ReportingService::class)->menuItemSalesSummary(
            now()->startOfDay(),
            now()->endOfDay(),
            null,
            null,
            'lounge',
        );

        $this->assertEqualsWithDelta(12000, $barReport['grand_total']['total_amount'], 0.01);
        $this->assertEqualsWithDelta(18000, $loungeReport['grand_total']['total_amount'], 0.01);
        $this->assertSame('Gin & Tonic', $barReport['categories'][0]['subcategories'][0]['items'][0]['name']);
        $this->assertSame('Old Fashioned', $loungeReport['categories'][0]['subcategories'][0]['items'][0]['name']);
    }

    public function test_bar_item_sales_summary_pdf_export_downloads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.bar-sales-summary.export-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
