<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\HBMS;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\HBMS\AvailabilityRequest;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    use RespondsWithJsonApi;

    public function index(AvailabilityRequest $request): JsonResponse
    {
        $checkIn = $request->validated('check_in');
        $checkOut = $request->validated('check_out');
        $adults = (int) $request->validated('adults', 1);

        $roomTypes = RoomType::query()
            ->where('max_adults', '>=', $adults)
            ->orderBy('name')
            ->get();

        $availability = $roomTypes->map(function (RoomType $roomType) use ($checkIn, $checkOut) {
            $totalRooms = Room::query()
                ->where('room_type_id', $roomType->id)
                ->whereNotIn('status', ['out_of_order', 'blocked'])
                ->count();

            $blocked = Reservation::query()
                ->where('room_type_id', $roomType->id)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in_date', '<', $checkOut)
                ->where('check_out_date', '>', $checkIn)
                ->count();

            $available = max(0, $totalRooms - $blocked);

            return [
                'room_type_id' => $roomType->id,
                'code' => $roomType->code,
                'name' => $roomType->name,
                'base_rate' => $roomType->base_rate,
                'available_rooms' => $available,
                'max_adults' => $roomType->max_adults,
            ];
        });

        return $this->respond($availability->values()->all());
    }
}
