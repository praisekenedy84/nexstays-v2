<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Events\GuestCheckedIn;
use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\Shared\Services\FolioService;
use DomainException;
use Illuminate\Support\Facades\DB;

class CheckInGuest
{
    public function __construct(
        private readonly FolioService $folioService
    ) {}

    public function execute(Reservation $reservation, array $options = []): Folio
    {
        return DB::transaction(function () use ($reservation, $options) {
            throw_if(
                $reservation->status !== 'confirmed',
                DomainException::class,
                "Reservation {$reservation->booking_ref} is not in confirmed status."
            );

            $roomId = $options['room_id'] ?? $reservation->room_id;

            throw_if(
                $roomId === null,
                DomainException::class,
                'A room must be assigned before check-in.'
            );

            $room = Room::query()->whereKey($roomId)->lockForUpdate()->firstOrFail();

            throw_if(
                $room->status !== 'vacant_clean',
                DomainException::class,
                "Room {$room->room_number} is not available for check-in."
            );

            $reservation->update([
                'status' => 'checked_in',
                'room_id' => $room->id,
            ]);

            $room->update(['status' => 'occupied']);

            $folio = $this->folioService->openFolio($reservation->fresh());

            GuestCheckedIn::dispatch($reservation, $room, $folio);

            return $folio;
        });
    }
}
