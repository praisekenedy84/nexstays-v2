<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HBMS\AvailabilityRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function index(Request $request): View
    {
        $checkIn = $request->input('check_in', now()->addDay()->toDateString());
        $checkOut = $request->input('check_out', now()->addDays(3)->toDateString());
        $adults = (int) $request->input('adults', 2);
        $children = (int) $request->input('children', 0);
        $roomTypeFilter = $request->input('room_type');

        $roomTypes = RoomType::query()->orderBy('name')->get();

        $validated = validator(
            [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $adults,
            ],
            (new AvailabilityRequest)->rules()
        )->validate();

        $checkIn = $validated['check_in'];
        $checkOut = $validated['check_out'];
        $adults = (int) ($validated['adults'] ?? $adults);

        $availability = $this->searchAvailability($checkIn, $checkOut, $adults);

        if ($roomTypeFilter && $roomTypeFilter !== 'all') {
            $availability = $availability->filter(
                fn (array $row) => $row['room_type_id'] === $roomTypeFilter
            )->values();
        }

        $lastReservation = Reservation::query()
            ->with(['guest', 'roomType'])
            ->latest()
            ->first();

        return view('hbms.availability', compact(
            'checkIn',
            'checkOut',
            'adults',
            'children',
            'roomTypes',
            'availability',
            'roomTypeFilter',
            'lastReservation',
        ));
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function searchAvailability(string $checkIn, string $checkOut, int $adults)
    {
        return RoomType::query()
            ->where('max_adults', '>=', $adults)
            ->orderBy('name')
            ->get()
            ->map(function (RoomType $roomType) use ($checkIn, $checkOut) {
                $totalRooms = Room::query()
                    ->where('room_type_id', $roomType->id)
                    ->whereIn('status', ['vacant_clean', 'vacant_dirty'])
                    ->count();

                $blocked = Reservation::query()
                    ->where('room_type_id', $roomType->id)
                    ->whereIn('status', ['confirmed', 'checked_in'])
                    ->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn)
                    ->count();

                return [
                    'room_type_id' => $roomType->id,
                    'code' => $roomType->code,
                    'name' => $roomType->name,
                    'description' => $roomType->description,
                    'base_rate' => $roomType->base_rate,
                    'amenities' => $roomType->amenities ?? [],
                    'photos' => $roomType->photos ?? [],
                    'available_rooms' => max(0, $totalRooms - $blocked),
                    'max_adults' => $roomType->max_adults,
                    'hot' => $blocked >= $totalRooms && $totalRooms > 0,
                ];
            })
            ->filter(fn (array $row) => $row['available_rooms'] > 0)
            ->values();
    }
}
