<?php

namespace Tests\Unit\HBMS;

use App\Domain\HBMS\Actions\CheckInGuest;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use DomainException;
use Tests\TenantTestCase;

class CheckInGuestTest extends TenantTestCase
{
    public function test_it_opens_a_folio_on_check_in(): void
    {
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'blocked',
        ]);
        $reservation = Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
        ]);

        $folio = app(CheckInGuest::class)->execute($reservation);

        $this->assertSame('checked_in', $reservation->fresh()->status);
        $this->assertSame('occupied', $room->fresh()->status);
        $this->assertDatabaseHas('folios', [
            'id' => $folio->id,
            'reservation_id' => $reservation->id,
            'status' => 'open',
        ]);
    }

    public function test_it_rejects_check_in_for_non_confirmed_reservation(): void
    {
        $reservation = Reservation::factory()->create(['status' => 'cancelled']);

        $this->expectException(DomainException::class);

        app(CheckInGuest::class)->execute($reservation);
    }
}
