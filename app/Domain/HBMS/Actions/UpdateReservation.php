<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
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

            $payload = collect($data)->only([
                'room_id',
                'check_in_date',
                'check_out_date',
                'adults',
                'children',
                'rate_plan_id',
                'special_requests',
                'deposit_amount',
            ])->map(function ($value, $key) {
                if ($key === 'special_requests' && blank($value)) {
                    return null;
                }

                return $value;
            })->filter(fn ($value, $key) => $value !== null || $key === 'special_requests')->all();

            $targetRoomId = $payload['room_id'] ?? $reservation->room_id;
            $targetCheckIn = $payload['check_in_date'] ?? $reservation->check_in_date?->toDateString();
            $targetCheckOut = $payload['check_out_date'] ?? $reservation->check_out_date?->toDateString();
            $requiresAvailabilityCheck = $targetRoomId !== null
                && $targetCheckIn !== null
                && $targetCheckOut !== null
                && (
                    array_key_exists('room_id', $payload)
                    || array_key_exists('check_in_date', $payload)
                    || array_key_exists('check_out_date', $payload)
                );

            if ($requiresAvailabilityCheck) {
                $previousRoomId = $reservation->room_id;
                $room = Room::query()
                    ->with('roomType')
                    ->whereKey($targetRoomId)
                    ->lockForUpdate()
                    ->firstOrFail();

                throw_if(
                    in_array($room->status, ['out_of_order', 'blocked'], true),
                    DomainException::class,
                    "Room {$room->room_number} is not available for booking."
                );

                $hasOverlap = Reservation::query()
                    ->where('room_id', $room->id)
                    ->whereKeyNot($reservation->id)
                    ->whereIn('status', ['inquiry', 'confirmed', 'checked_in'])
                    ->where('check_in_date', '<', $targetCheckOut)
                    ->where('check_out_date', '>', $targetCheckIn)
                    ->exists();

                throw_if(
                    $hasOverlap,
                    DomainException::class,
                    "Room {$room->room_number} is already reserved for selected dates."
                );

                $payload['room_type_id'] = $room->room_type_id;
                $payload['daily_rate'] = (string) ($room->daily_rate ?? $room->roomType?->base_rate ?? 0);

                if (array_key_exists('room_id', $payload) && $previousRoomId !== null && $previousRoomId !== $room->id) {
                    $previousRoom = Room::query()->whereKey($previousRoomId)->lockForUpdate()->first();
                    if ($previousRoom !== null && in_array($previousRoom->status, ['blocked', 'occupied'], true)) {
                        $previousRoom->update(['status' => 'vacant_clean']);
                    }
                }

                if (array_key_exists('room_id', $payload) && $reservation->status !== 'checked_in') {
                    $room->update([
                        'status' => $reservation->status === 'inquiry' ? 'blocked' : 'occupied',
                    ]);
                }
            }

            $reservation->update($payload);

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
