<?php

declare(strict_types=1);

namespace Tests\Feature\Facilities;

use App\Domain\Facilities\Models\FacilityAttendance;
use App\Domain\HBMS\Models\Guest;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Till\Models\TillSession;
use Tests\TenantTestCase;

class FacilityAttendanceTest extends TenantTestCase
{
    public function test_pool_desk_page_loads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.facilities.pool'));

        $response->assertOk();
        $response->assertSee('Swimming Pool');
        $response->assertSee('Record visit');
    }

    public function test_gym_desk_page_loads(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.facilities.gym'));

        $response->assertOk();
        $response->assertSee('Gym');
    }

    public function test_walk_in_pool_attendance_with_cash_payment(): void
    {
        $session = TillSession::query()->create([
            'outlet_id' => null,
            'opened_by' => $this->user->id,
            'float_amount' => 50000,
            'currency' => 'TZS',
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.facilities.attendance.store'), [
                'facility_type' => 'pool',
                'visitor_type' => 'walk_in',
                'visitor_name' => 'Jane Doe',
                'party_size' => 4,
                'amount' => 15000,
                'settlement' => 'cash',
                'till_session_id' => $session->id,
                'cash_tendered' => 20000,
            ]);

        $response->assertRedirect(route('tenant.facilities.pool'));
        $response->assertSessionHas('success');

        $attendance = FacilityAttendance::query()->first();
        $this->assertNotNull($attendance);
        $this->assertSame('pool', $attendance->facility_type);
        $this->assertSame('Jane Doe', $attendance->visitor_name);
        $this->assertSame(4, $attendance->party_size);
        $this->assertNull($attendance->reservation_id);
        $this->assertSame('cash', $attendance->settlement);
        $this->assertNotNull($attendance->payment_id);
        $this->assertSame(15000.0, (float) $attendance->amount);
    }

    public function test_party_size_defaults_to_one_when_not_provided(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.facilities.attendance.store'), [
                'facility_type' => 'pool',
                'visitor_type' => 'walk_in',
                'visitor_name' => 'Solo Visitor',
                'amount' => 5000,
                'settlement' => 'cash',
            ]);

        $response->assertRedirect(route('tenant.facilities.pool'));

        $attendance = FacilityAttendance::query()->where('visitor_name', 'Solo Visitor')->first();
        $this->assertSame(1, $attendance->party_size);
    }

    public function test_walk_in_gym_attendance_with_cash_payment_and_no_till(): void
    {
        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.facilities.attendance.store'), [
                'facility_type' => 'gym',
                'visitor_type' => 'walk_in',
                'visitor_name' => 'No Till Member',
                'amount' => 10000,
                'settlement' => 'cash',
            ]);

        $response->assertRedirect(route('tenant.facilities.gym'));
        $response->assertSessionHas('success');

        $attendance = FacilityAttendance::query()->where('facility_type', 'gym')->first();
        $this->assertNotNull($attendance);
        $this->assertSame('No Till Member', $attendance->visitor_name);
        $this->assertSame('cash', $attendance->settlement);
        $this->assertNotNull($attendance->payment_id);
        $this->assertNull($attendance->till_session_id);
    }

    public function test_hotel_guest_gym_charge_to_folio(): void
    {
        $roomType = RoomType::factory()->create();
        $guest = Guest::factory()->create(['first_name' => 'Ali', 'last_name' => 'Hassan']);
        $reservation = Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'guest_id' => $guest->id,
            'status' => 'checked_in',
        ]);
        app(FolioService::class)->openFolio($reservation);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.facilities.attendance.store'), [
                'facility_type' => 'gym',
                'visitor_type' => 'hotel_guest',
                'reservation_id' => $reservation->id,
                'amount' => 10000,
                'settlement' => 'folio',
            ]);

        $response->assertRedirect(route('tenant.facilities.gym'));

        $attendance = FacilityAttendance::query()->where('facility_type', 'gym')->first();
        $this->assertNotNull($attendance);
        $this->assertSame('Ali Hassan', $attendance->visitor_name);
        $this->assertNotNull($attendance->folio_transaction_id);
        $this->assertNull($attendance->payment_id);
    }

    public function test_pool_attendance_report_loads(): void
    {
        FacilityAttendance::query()->create([
            'facility_type' => 'pool',
            'visitor_name' => 'Walk-in Guest',
            'amount' => 15000,
            'currency' => 'TZS',
            'settlement' => 'cash',
            'recorded_by' => $this->user->id,
            'attended_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reports.pool-attendance', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertSee('Swimming Pool');
        $response->assertSee('Walk-in Guest');
    }
}
