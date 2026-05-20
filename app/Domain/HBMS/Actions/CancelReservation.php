<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\Reservation;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelReservation
{
    private const CANCELLABLE_STATUSES = ['inquiry', 'confirmed'];

    public function execute(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation) {
            throw_if(
                ! in_array($reservation->status, self::CANCELLABLE_STATUSES, true),
                DomainException::class,
                "Cannot cancel a reservation with status: {$reservation->status}"
            );

            $reservation->update(['status' => 'cancelled']);

            if ($reservation->room !== null) {
                $reservation->room->update(['status' => 'vacant_clean']);
            }

            return $reservation->fresh();
        });
    }
}
