<?php

namespace Tests\Feature\HBMS;

use App\Domain\HBMS\Models\Guest;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use Tests\TenantTestCase;

class ReservationApiTest extends TenantTestCase
{
    public function test_it_creates_a_reservation(): void
    {
        $guest = Guest::factory()->create();
        $roomType = RoomType::factory()->create(['base_rate' => '200000.00']);

        $response = $this->tenantJson('POST', '/api/v1/reservations', [
            'guest_id' => $guest->id,
            'room_type_id' => $roomType->id,
            'check_in_date' => now()->addDay()->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
            'adults' => 2,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'reservation')
            ->assertJsonPath('data.attributes.status', 'confirmed');

        $this->assertDatabaseHas('reservations', [
            'guest_id' => $guest->id,
            'room_type_id' => $roomType->id,
        ]);
    }

    public function test_it_checks_in_and_out_a_guest(): void
    {
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'vacant_clean',
        ]);
        $reservation = Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
        ]);

        $checkIn = $this->tenantJson('POST', "/api/v1/reservations/{$reservation->id}/check-in");
        $checkIn->assertOk()
            ->assertJsonPath('data.folio.type', 'folio');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'checked_in',
        ]);

        $checkOut = $this->tenantJson('POST', "/api/v1/reservations/{$reservation->id}/check-out");
        $checkOut->assertOk()
            ->assertJsonPath('data.attributes.status', 'checked_out');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'vacant_dirty',
        ]);
    }

    public function test_it_cancels_a_confirmed_reservation(): void
    {
        $reservation = Reservation::factory()->create();

        $response = $this->tenantJson('DELETE', "/api/v1/reservations/{$reservation->id}");

        $response->assertOk()
            ->assertJsonPath('data.attributes.status', 'cancelled');
    }
}
