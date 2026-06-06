<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Services\DivisionSalesService;
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
}
