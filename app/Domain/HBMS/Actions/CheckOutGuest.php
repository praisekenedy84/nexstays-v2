<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\NotificationService;
use DomainException;
use Illuminate\Support\Facades\DB;

class CheckOutGuest
{
    public function __construct(
        private readonly FolioService $folioService,
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation) {
            throw_if(
                $reservation->status !== 'checked_in',
                DomainException::class,
                "Cannot check out a reservation with status: {$reservation->status}"
            );

            $folio = $reservation->folio;

            throw_if(
                $folio === null,
                DomainException::class,
                'No folio exists for this reservation.'
            );

            $checkOutDate = $reservation->check_out_date?->startOfDay();
            $today        = now()->startOfDay();

            if ($reservation->isOverstay() && $reservation->overstay_settlement === null) {
                $nights     = (int) $checkOutDate->diffInDays($today);
                $nightLabel = $nights === 1 ? 'night' : 'nights';
                throw new DomainException(
                    "Guest overstayed by {$nights} {$nightLabel}. Post the overstay charge to the folio first."
                );
            }

            if ($reservation->hasPendingOverstay()) {
                throw new DomainException(
                    'Overstay charge is on the folio but not yet settled. Collect payment or waive with a reason before check-out.'
                );
            }

            $balance = $this->folioService->balance($folio);

            throw_if(
                ! $balance->isZero(),
                DomainException::class,
                'Folio balance must be zero before check-out.'
            );

            $reservation->update(['status' => 'checked_out', 'checked_out_at' => now()]);

            if ($reservation->room !== null) {
                $reservation->room->update(['status' => 'vacant_dirty']);
            }

            $settled = (string) $folio->payments()->sum('amount');
            $folio->update([
                'status'         => 'closed',
                'settled_amount' => $settled,
            ]);

            $fresh = $reservation->fresh(['guest', 'room', 'roomType', 'ratePlan', 'folio']);

            $guest = $fresh->guest;
            $room  = $fresh->room;
            $this->notificationService->notify(
                type:  'check_out',
                title: 'Check-out: '.($guest ? "{$guest->first_name} {$guest->last_name}" : $reservation->booking_ref),
                body:  ($room ? "Room {$room->room_number} · " : '')."{$reservation->booking_ref} · settled",
                data:  ['booking_ref' => $reservation->booking_ref],
            );

            return $fresh;
        });
    }
}
