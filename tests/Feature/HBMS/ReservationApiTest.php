<?php

namespace Tests\Feature\HBMS;

use App\Domain\HBMS\Models\Guest;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TenantTestCase;

class ReservationApiTest extends TenantTestCase
{
    public function test_it_creates_a_reservation(): void
    {
        config()->set('services.textify.api_key', 'test-textify-key');
        config()->set('services.textify.sender_name', 'NexStay');
        config()->set('services.textify.base_url', 'https://portal.textify.africa/api');
        config()->set('services.textify.default_country_code', '255');
        Http::fake([
            'https://portal.textify.africa/api/message/create' => Http::response([
                'success' => true,
                'status_code' => 200,
            ], 200),
        ]);

        $guest = Guest::factory()->create([
            'phone' => '0756628215',
        ]);
        $roomType = RoomType::factory()->create(['base_rate' => '200000.00']);
        $room = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'daily_rate' => '215000.00',
        ]);

        $response = $this->tenantJson('POST', '/api/v1/reservations', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->addDay()->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
            'adults' => 2,
            'source' => 'cash',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'reservation')
            ->assertJsonPath('data.attributes.status', 'confirmed')
            ->assertJsonPath('data.attributes.created_by', $this->user->id)
            ->assertJsonPath('data.relationships.creator.name', $this->user->name);

        $this->assertDatabaseHas('reservations', [
            'guest_id' => $guest->id,
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'daily_rate' => '215000.00',
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'occupied',
        ]);

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://portal.textify.africa/api/message/create'
                && $request->hasHeader('Authorization', 'test-textify-key')
                && $request['sender_name'] === 'NexStay'
                && $request['messages'][0]['receiver'] === '255756628215'
                && str_contains((string) $request['messages'][0]['content'], 'has been confirmed');
        });
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
        config()->set('services.textify.api_key', 'test-textify-key');
        config()->set('services.textify.sender_name', 'NexStay');
        config()->set('services.textify.base_url', 'https://portal.textify.africa/api');
        Http::fake([
            'https://portal.textify.africa/api/message/create' => Http::response([
                'success' => true,
                'status_code' => 200,
            ], 200),
        ]);

        $guest = Guest::factory()->create([
            'phone' => '255756628215',
        ]);
        $reservation = Reservation::factory()->create([
            'guest_id' => $guest->id,
        ]);

        $response = $this->tenantJson('DELETE', "/api/v1/reservations/{$reservation->id}");

        $response->assertOk()
            ->assertJsonPath('data.attributes.status', 'cancelled');

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://portal.textify.africa/api/message/create'
                && $request['messages'][0]['receiver'] === '255756628215'
                && str_contains((string) $request['messages'][0]['content'], 'has been cancelled');
        });
    }

    public function test_it_updates_room_status_when_reassigning_a_reservation(): void
    {
        $roomType = RoomType::factory()->create();
        $oldRoom = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'occupied',
        ]);
        $newRoom = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'vacant_clean',
        ]);
        $reservation = Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'room_id' => $oldRoom->id,
            'status' => 'confirmed',
        ]);

        $response = $this->tenantJson('PATCH', "/api/v1/reservations/{$reservation->id}", [
            'room_id' => $newRoom->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.attributes.room_id', $newRoom->id);

        $this->assertDatabaseHas('rooms', [
            'id' => $oldRoom->id,
            'status' => 'vacant_clean',
        ]);
        $this->assertDatabaseHas('rooms', [
            'id' => $newRoom->id,
            'status' => 'occupied',
        ]);
    }
}
