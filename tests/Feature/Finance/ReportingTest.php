<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\ReportingService;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

class ReportingTest extends TenantTestCase
{
    public function test_fb_revenue_split_groups_food_and_drinks(): void
    {
        $reservation = Reservation::factory()->create(['room_type_id' => RoomType::factory()->create()->id]);
        $folio = app(FolioService::class)->openFolio($reservation);

        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'restaurant',
            'description' => 'Dinner',
            'amount' => 50000,
            'tax_amount' => 9000,
            'posted_at' => now(),
        ]);
        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'bar',
            'description' => 'Drinks',
            'amount' => 30000,
            'tax_amount' => 5400,
            'posted_at' => now(),
        ]);

        $split = app(ReportingService::class)->fbRevenueSplit(now()->startOfDay(), now()->endOfDay());

        $this->assertEquals('50000', $split['food']);
        $this->assertEquals('30000', $split['drinks']);
    }

    public function test_room_reservation_finance_summarizes_revenue_and_deposits(): void
    {
        Reservation::factory()->create([
            'status' => 'confirmed',
            'check_in_date' => now()->addDay()->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
            'daily_rate' => '100000.00',
            'deposit_amount' => '50000.00',
        ]);

        Reservation::factory()->create([
            'status' => 'checked_in',
            'check_in_date' => now()->addDays(2)->toDateString(),
            'check_out_date' => now()->addDays(5)->toDateString(),
            'daily_rate' => '120000.00',
            'deposit_amount' => '30000.00',
        ]);

        $report = app(ReportingService::class)->roomReservationFinance(now()->startOfDay(), now()->addDays(7)->endOfDay());

        $this->assertEquals(2, $report['total_reservations']);
        $this->assertEquals(5, $report['room_nights']);
        $this->assertEquals('560000.00', $report['projected_room_revenue']);
        $this->assertEquals('560000.00', $report['deposits_collected']);
        $this->assertEquals('0.00', $report['balance_expected_on_arrival']);
        $this->assertEquals(1, $report['status_counts']['confirmed']);
        $this->assertEquals(1, $report['status_counts']['checked_in']);
    }

    public function test_room_payments_accounting_includes_guest_room_nights_and_balances(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'checked_in',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'daily_rate' => '100000.00',
            'deposit_amount' => '20000.00',
        ]);

        $folio = app(FolioService::class)->openFolio($reservation);

        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'restaurant',
            'description' => 'Dinner',
            'amount' => 30000,
            'tax_amount' => 5400,
            'posted_at' => now(),
        ]);

        $report = app(ReportingService::class)->roomPaymentsAccounting(now()->startOfDay(), now()->addDays(3)->endOfDay());

        $this->assertCount(1, $report['rows']);
        $this->assertEquals(2, $report['totals']['room_nights']);
        $this->assertEquals('200000.00', $report['totals']['room_revenue']);
        $this->assertEquals('30000.00', $report['totals']['folio_charges']);
        $this->assertEquals('200000.00', $report['totals']['payments_received']);
        $this->assertEquals('0.00', $report['totals']['outstanding_balance']);
    }

    public function test_room_payments_accounting_csv_export_downloads(): void
    {
        $reservation = Reservation::factory()->create([
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(1)->toDateString(),
            'daily_rate' => '95000.00',
            'deposit_amount' => '10000.00',
        ]);

        $response = $this
            ->withoutMiddleware([
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
            ])
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.room-payments-accounting.export', [
                'from' => now()->toDateString(),
                'to' => now()->addDays(2)->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition');
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Booking Ref', $csv);
        $this->assertStringContainsString($reservation->booking_ref, $csv);
    }

    public function test_room_payments_accounting_excel_export_downloads(): void
    {
        $reservation = Reservation::factory()->create([
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(1)->toDateString(),
            'daily_rate' => '99000.00',
        ]);

        $response = $this
            ->withoutMiddleware([
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
            ])
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.room-payments-accounting.export-excel', [
                'from' => now()->toDateString(),
                'to' => now()->addDays(2)->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertHeader('content-disposition');
        $excel = $response->streamedContent();

        $this->assertStringContainsString('<table', $excel);
        $this->assertStringContainsString('Booking Ref', $excel);
        $this->assertStringContainsString($reservation->booking_ref, $excel);
    }
}
