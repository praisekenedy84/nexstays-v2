<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Events\GuestCheckedIn;
use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\NotificationService;
use App\Domain\HBMS\Services\ReservationSettingsService;
use Brick\Money\Money;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class CheckInGuest
{
    public function __construct(
        private readonly FolioService $folioService,
        private readonly NotificationService $notificationService,
        private readonly ReservationSettingsService $reservationSettings,
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
                ! in_array($room->status, ['vacant_clean', 'blocked', 'occupied'], true),
                DomainException::class,
                "Room {$room->room_number} is not available for check-in."
            );

            $reservation->update([
                'status'        => 'checked_in',
                'room_id'       => $room->id,
                'checked_in_at' => now(),
            ]);

            $room->update(['status' => 'occupied']);

            $folio = $this->folioService->openFolio($reservation->fresh());

            $paymentMode = $this->reservationSettings->all()['payment_mode'] ?? 'prepaid';

            if ($paymentMode === 'prepaid') {
                $stayNights = max(
                    1,
                    Carbon::parse($reservation->check_in_date)->diffInDays(Carbon::parse($reservation->check_out_date))
                );
                $roomGross = Money::of((string) $reservation->daily_rate, $folio->currency)->multipliedBy($stayNights);

                if ($roomGross->isPositive()) {
                    $this->folioService->recordPrepaidRoomCharge($folio, $roomGross);
                }
            }

            GuestCheckedIn::dispatch($reservation, $room, $folio);

            $guest = $reservation->guest;
            $this->notificationService->notify(
                type:  'check_in',
                title: 'Check-in: '.($guest ? "{$guest->first_name} {$guest->last_name}" : $reservation->booking_ref),
                body:  "Room {$room->room_number} · {$reservation->booking_ref} · out {$reservation->check_out_date->format('d M')}",
                data:  ['booking_ref' => $reservation->booking_ref, 'room_number' => $room->room_number],
            );

            return $folio;
        });
    }
}
