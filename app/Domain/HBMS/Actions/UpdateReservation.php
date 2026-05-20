<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\Reservation;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateReservation
{
    private const ALLOWED_STATUSES = ['inquiry', 'confirmed'];

    public function execute(Reservation $reservation, array $data): Reservation
    {
        return DB::transaction(function () use ($reservation, $data) {
            throw_if(
                ! in_array($reservation->status, self::ALLOWED_STATUSES, true),
                DomainException::class,
                "Cannot update a reservation with status: {$reservation->status}"
            );

            $reservation->update(collect($data)->only([
                'room_id',
                'check_in_date',
                'check_out_date',
                'adults',
                'children',
                'rate_plan_id',
                'daily_rate',
                'special_requests',
                'deposit_amount',
            ])->filter(fn ($value) => $value !== null)->all());

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
