<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\Shared\Services\NotificationService;
use App\Domain\Shared\Services\TextifySmsService;
use App\Domain\HBMS\Support\BookingReferenceGenerator;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateReservation
{
    public function __construct(
        private readonly TextifySmsService $smsService,
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(array $data): Reservation
    {
        $reservation = DB::transaction(function () use ($data) {
            $room = Room::query()
                ->with('roomType')
                ->whereKey($data['room_id'])
                ->lockForUpdate()
                ->firstOrFail();

            throw_if(
                in_array($room->status, ['out_of_order', 'blocked'], true),
                DomainException::class,
                "Room {$room->room_number} is not available for booking."
            );

            $hasOverlap = Reservation::query()
                ->where('room_id', $room->id)
                ->whereIn('status', ['inquiry', 'confirmed', 'checked_in'])
                ->where('check_in_date', '<', $data['check_out_date'])
                ->where('check_out_date', '>', $data['check_in_date'])
                ->exists();

            throw_if(
                $hasOverlap,
                DomainException::class,
                "Room {$room->room_number} is already reserved for selected dates."
            );

            $reservation = Reservation::query()->create([
                'booking_ref' => BookingReferenceGenerator::generate(),
                'guest_id' => $data['guest_id'],
                'room_id' => $room->id,
                'room_type_id' => $room->room_type_id,
                'status' => $data['status'] ?? 'confirmed',
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'adults' => $data['adults'],
                'children' => $data['children'] ?? 0,
                'rate_plan_id' => $data['rate_plan_id'] ?? null,
                'daily_rate' => (string) ($room->daily_rate ?? $room->roomType?->base_rate ?? 0),
                'source' => $data['source'] ?? 'cash',
                'ota_ref' => $data['ota_ref'] ?? null,
                'special_requests' => filled($data['special_requests'] ?? null) ? $data['special_requests'] : null,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'created_by' => Auth::id(),
            ]);

            if ($reservation->status === 'inquiry') {
                $room->update(['status' => 'blocked']);
            }

            if ($reservation->status === 'confirmed') {
                $room->update(['status' => 'occupied']);
            }

            return $reservation;
        });

        $this->smsService->sendReservationCreated($reservation);

        $guest = $reservation->guest;
        $this->notificationService->notify(
            type:  'reservation_created',
            title: "New reservation {$reservation->booking_ref}",
            body:  ($guest ? "{$guest->first_name} {$guest->last_name}" : 'Guest')
                   ." · {$reservation->check_in_date->format('d M')} – {$reservation->check_out_date->format('d M')}",
            data:  ['booking_ref' => $reservation->booking_ref, 'reservation_id' => $reservation->id],
        );

        return $reservation;
    }
}
