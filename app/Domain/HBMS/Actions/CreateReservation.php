<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\RatePlanPrice;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\HBMS\Support\BookingReferenceGenerator;
use Illuminate\Support\Facades\DB;

class CreateReservation
{
    public function execute(array $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            $roomType = RoomType::query()->findOrFail($data['room_type_id']);
            $checkInDate = $data['check_in_date'];

            return Reservation::query()->create([
                'booking_ref' => BookingReferenceGenerator::generate(),
                'guest_id' => $data['guest_id'],
                'room_id' => $data['room_id'] ?? null,
                'room_type_id' => $roomType->id,
                'status' => $data['status'] ?? 'confirmed',
                'check_in_date' => $checkInDate,
                'check_out_date' => $data['check_out_date'],
                'adults' => $data['adults'],
                'children' => $data['children'] ?? 0,
                'rate_plan_id' => $data['rate_plan_id'] ?? null,
                'daily_rate' => $this->resolveDailyRate($data, $roomType, $checkInDate),
                'source' => $data['source'] ?? null,
                'ota_ref' => $data['ota_ref'] ?? null,
                'special_requests' => $data['special_requests'] ?? null,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
            ]);
        });
    }

    private function resolveDailyRate(array $data, RoomType $roomType, string $checkInDate): string
    {
        if (! empty($data['daily_rate'])) {
            return (string) $data['daily_rate'];
        }

        if (! empty($data['rate_plan_id'])) {
            $price = RatePlanPrice::query()
                ->where('rate_plan_id', $data['rate_plan_id'])
                ->where('room_type_id', $roomType->id)
                ->whereDate('date', $checkInDate)
                ->value('price');

            if ($price !== null) {
                return (string) $price;
            }
        }

        return (string) $roomType->base_rate;
    }
}
