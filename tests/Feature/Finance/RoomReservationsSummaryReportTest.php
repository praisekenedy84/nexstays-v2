<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Services\ReportingService;
use Tests\TenantTestCase;

class RoomReservationsSummaryReportTest extends TenantTestCase
{
    public function test_room_reservations_summary_groups_by_room_type_and_status(): void
    {
        $roomType = RoomType::factory()->create(['name' => 'Deluxe Double']);

        Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'confirmed',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'daily_rate' => '100000.00',
            'deposit_amount' => '50000.00',
        ]);

        $report = app(ReportingService::class)->roomReservationsSummary(
            now()->startOfDay(),
            now()->endOfDay(),
            null,
            null,
        );

        $this->assertCount(1, $report['categories']);
        $this->assertSame('Deluxe Double', $report['categories'][0]['name']);
        $this->assertSame('Confirmed', $report['categories'][0]['subcategories'][0]['name']);
        $this->assertEquals(2, $report['categories'][0]['subcategories'][0]['items'][0]['quantity']);
        $this->assertEqualsWithDelta(200000, $report['grand_total']['amount'], 0.01);
    }

    public function test_room_reservations_summary_page_loads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.room-reservations-summary'));

        $response->assertOk();
        $response->assertSee('Room reservations summary');
    }

    public function test_room_reservations_summary_excel_export_downloads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.room-reservations-summary.export-excel', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->assertStringContainsString('Room Reservations Summary', $response->streamedContent());
    }

    public function test_room_reservations_summary_pdf_export_downloads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.room-reservations-summary.export-pdf', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
