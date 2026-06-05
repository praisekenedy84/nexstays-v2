<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Services\DivisionSalesService;
use Tests\TenantTestCase;

class DashboardOverviewTest extends TenantTestCase
{
    public function test_booked_room_revenue_includes_confirmed_reservations(): void
    {
        $roomType = RoomType::factory()->create();

        Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'confirmed',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'daily_rate' => '100000.00',
        ]);

        Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'inquiry',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'daily_rate' => '50000.00',
        ]);

        $todayBooked = app(DivisionSalesService::class)->todayArrivalBookedRevenue();
        $mtdBooked = app(DivisionSalesService::class)->bookedRoomRevenue(
            now()->startOfMonth(),
            now()->endOfDay(),
        );

        $this->assertEquals(200000.0, $todayBooked['revenue']);
        $this->assertEquals(1, $todayBooked['reservation_count']);
        $this->assertEquals(200000.0, $mtdBooked['revenue']);
    }
}
