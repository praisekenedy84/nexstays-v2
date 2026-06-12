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

    public function test_walk_in_attendance_can_be_voided_and_reverses_payment(): void
    {
        $session = TillSession::query()->create([
            'outlet_id' => null,
            'opened_by' => $this->user->id,
            'float_amount' => 50000,
            'currency' => 'TZS',
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.facilities.attendance.store'), [
                'facility_type' => 'pool',
                'visitor_type' => 'walk_in',
                'visitor_name' => 'To Be Voided',
                'amount' => 15000,
                'settlement' => 'cash',
                'till_session_id' => $session->id,
                'cash_tendered' => 15000,
            ]);

        $attendance = FacilityAttendance::query()->where('visitor_name', 'To Be Voided')->first();
        $paymentId = $attendance->payment_id;
        $this->assertNotNull($paymentId);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->delete(route('tenant.facilities.attendance.void', $attendance), [
                'reason' => 'Recorded by mistake',
            ]);

        $response->assertRedirect(route('tenant.facilities.pool'));
        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertTrue($attendance->isVoided());
        $this->assertSame('Recorded by mistake', $attendance->void_reason);
        $this->assertSame($this->user->id, $attendance->voided_by);
        $this->assertDatabaseMissing('payments', ['id' => $paymentId]);
    }

    public function test_hotel_guest_folio_attendance_voids_folio_transaction(): void
    {
        $roomType = RoomType::factory()->create();
        $guest = Guest::factory()->create(['first_name' => 'Voided', 'last_name' => 'Guest']);
        $reservation = Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'guest_id' => $guest->id,
            'status' => 'checked_in',
        ]);
        app(FolioService::class)->openFolio($reservation);

        $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.facilities.attendance.store'), [
                'facility_type' => 'gym',
                'visitor_type' => 'hotel_guest',
                'reservation_id' => $reservation->id,
                'amount' => 10000,
                'settlement' => 'folio',
            ]);

        $attendance = FacilityAttendance::query()->where('reservation_id', $reservation->id)->first();
        $folioTransactionId = $attendance->folio_transaction_id;
        $this->assertNotNull($folioTransactionId);

        $this->web()
            ->actingAs($this->user, 'web')
            ->delete(route('tenant.facilities.attendance.void', $attendance), [
                'reason' => 'Wrong guest',
            ]);

        $attendance->refresh();
        $this->assertTrue($attendance->isVoided());
        $this->assertDatabaseHas('folio_transactions', [
            'id' => $folioTransactionId,
            'void_reason' => 'Wrong guest',
        ]);
        $this->assertNotNull(\App\Domain\HBMS\Models\FolioTransaction::find($folioTransactionId)->voided_at);
    }

    public function test_voided_attendance_cannot_be_voided_again(): void
    {
        $attendance = FacilityAttendance::query()->create([
            'facility_type' => 'pool',
            'visitor_name' => 'Already Voided',
            'amount' => 0,
            'currency' => 'TZS',
            'settlement' => 'complimentary',
            'recorded_by' => $this->user->id,
            'attended_at' => now(),
            'voided_at' => now(),
            'void_reason' => 'Initial void',
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->delete(route('tenant.facilities.attendance.void', $attendance), [
                'reason' => 'Second attempt',
            ]);

        $response->assertRedirect(route('tenant.facilities.pool'));
        $response->assertSessionHas('error');
    }

    public function test_attendance_receipt_page_loads(): void
    {
        $attendance = FacilityAttendance::query()->create([
            'facility_type' => 'pool',
            'visitor_name' => 'Receipt Guest',
            'amount' => 15000,
            'currency' => 'TZS',
            'settlement' => 'cash',
            'recorded_by' => $this->user->id,
            'attended_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.facilities.attendance.receipt', $attendance));

        $response->assertOk();
        $response->assertSee('Receipt Guest');
        $response->assertSee('Print receipt');
    }

    public function test_attendance_edit_page_loads(): void
    {
        $attendance = FacilityAttendance::query()->create([
            'facility_type' => 'pool',
            'visitor_name' => 'Edit Me',
            'party_size' => 2,
            'amount' => 15000,
            'currency' => 'TZS',
            'settlement' => 'cash',
            'recorded_by' => $this->user->id,
            'attended_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.facilities.attendance.edit', $attendance));

        $response->assertOk();
        $response->assertSee('Edit Me');
        $response->assertSee('Save changes');
    }

    public function test_walk_in_attendance_amount_update_updates_payment(): void
    {
        $session = TillSession::query()->create([
            'outlet_id' => null,
            'opened_by' => $this->user->id,
            'float_amount' => 50000,
            'currency' => 'TZS',
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.facilities.attendance.store'), [
                'facility_type' => 'pool',
                'visitor_type' => 'walk_in',
                'visitor_name' => 'Edit Cash',
                'party_size' => 2,
                'amount' => 15000,
                'settlement' => 'cash',
                'till_session_id' => $session->id,
                'cash_tendered' => 20000,
            ]);

        $attendance = FacilityAttendance::query()->where('visitor_name', 'Edit Cash')->first();

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->patch(route('tenant.facilities.attendance.update', $attendance), [
                'visitor_name' => 'Edit Cash Updated',
                'party_size' => 3,
                'amount' => 18000,
                'notes' => 'Adjusted fee',
            ]);

        $response->assertRedirect(route('tenant.facilities.pool'));
        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertSame('Edit Cash Updated', $attendance->visitor_name);
        $this->assertSame(3, $attendance->party_size);
        $this->assertSame(18000.0, (float) $attendance->amount);
        $this->assertSame('Adjusted fee', $attendance->notes);

        $payment = $attendance->payment;
        $this->assertSame(18000.0, (float) $payment->amount);
        $this->assertSame(2000.0, (float) $payment->cash_change);
    }

    public function test_folio_attendance_amount_update_updates_folio_transaction_with_tax(): void
    {
        $roomType = RoomType::factory()->create();
        $guest = Guest::factory()->create(['first_name' => 'Edit', 'last_name' => 'Folio']);
        $reservation = Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'guest_id' => $guest->id,
            'status' => 'checked_in',
        ]);
        app(FolioService::class)->openFolio($reservation);

        $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.facilities.attendance.store'), [
                'facility_type' => 'gym',
                'visitor_type' => 'hotel_guest',
                'reservation_id' => $reservation->id,
                'amount' => 10000,
                'settlement' => 'folio',
            ]);

        $attendance = FacilityAttendance::query()->where('reservation_id', $reservation->id)->first();
        $folioTransactionId = $attendance->folio_transaction_id;

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->patch(route('tenant.facilities.attendance.update', $attendance), [
                'party_size' => 1,
                'amount' => 20000,
            ]);

        $response->assertRedirect(route('tenant.facilities.gym'));

        $attendance->refresh();
        $this->assertSame(20000.0, (float) $attendance->amount);

        $transaction = \App\Domain\HBMS\Models\FolioTransaction::find($folioTransactionId);
        $this->assertSame(20000.0, (float) $transaction->amount);
        $this->assertGreaterThan(0, (float) $transaction->tax_amount);
    }

    public function test_voided_attendance_cannot_be_edited(): void
    {
        $attendance = FacilityAttendance::query()->create([
            'facility_type' => 'pool',
            'visitor_name' => 'Already Voided',
            'amount' => 0,
            'currency' => 'TZS',
            'settlement' => 'complimentary',
            'recorded_by' => $this->user->id,
            'attended_at' => now(),
            'voided_at' => now(),
            'void_reason' => 'Initial void',
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->patch(route('tenant.facilities.attendance.update', $attendance), [
                'party_size' => 1,
                'amount' => 0,
            ]);

        $response->assertSessionHas('error');
    }

    public function test_complimentary_attendance_amount_must_stay_zero(): void
    {
        $attendance = FacilityAttendance::query()->create([
            'facility_type' => 'pool',
            'visitor_name' => 'Free Visit',
            'amount' => 0,
            'currency' => 'TZS',
            'settlement' => 'complimentary',
            'recorded_by' => $this->user->id,
            'attended_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->patch(route('tenant.facilities.attendance.update', $attendance), [
                'party_size' => 1,
                'amount' => 5000,
            ]);

        $response->assertSessionHas('error');

        $attendance->refresh();
        $this->assertSame(0.0, (float) $attendance->amount);
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
