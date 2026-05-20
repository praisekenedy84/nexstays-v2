<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Services\FolioService;
use DomainException;
use Illuminate\Support\Facades\DB;

class CheckOutGuest
{
    public function __construct(
        private readonly FolioService $folioService
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

            $balance = $this->folioService->balance($folio);

            throw_if(
                ! $balance->isZero(),
                DomainException::class,
                'Folio balance must be zero before check-out.'
            );

            $reservation->update(['status' => 'checked_out']);

            if ($reservation->room !== null) {
                $reservation->room->update(['status' => 'vacant_dirty']);
            }

            $folio->update(['status' => 'closed']);

            return $reservation->fresh([
                'guest',
                'room',
                'roomType',
                'ratePlan',
                'folio',
            ]);
        });
    }
}
