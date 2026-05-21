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
        $amenityFilter = trim((string) $request->input('amenity', ''));

        $roomTypes = RoomType::query()->orderBy('name')->get();
        $amenityOptions = $this->amenityOptions();

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

        $availability = $this->searchAvailability($checkIn, $checkOut, $adults, $amenityFilter);

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
            'amenityFilter',
            'amenityOptions',
            'lastReservation',
        ));
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function searchAvailability(
        string $checkIn,
        string $checkOut,
        int $adults,
        string $amenityFilter = '',
    )
    {
        $amenityNeedle = strtolower(trim($amenityFilter));

        return Room::query()
            ->select(['id', 'room_type_id', 'room_number', 'status', 'daily_rate', 'photos', 'amenities'])
            ->with([
                'roomType:id,code,name,description,base_rate,max_adults,amenities,photos',
            ])
            ->whereNotIn('status', ['out_of_order', 'blocked'])
            ->whereHas('roomType', fn ($query) => $query->where('max_adults', '>=', $adults))
            ->whereDoesntHave('reservations', function ($query) use ($checkIn, $checkOut) {
                $query
                    ->whereIn('status', ['confirmed', 'checked_in'])
                    ->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn);
            })
            ->orderBy('room_type_id')
            ->orderBy('room_number')
            ->get()
            ->map(function (Room $room) {
                $roomType = $room->roomType;
                $primaryPhoto = collect($room->photos ?? [])->first()
                    ?? collect($roomType?->photos ?? [])->first();
                $amenities = collect([$room->amenities ?? [], $roomType?->amenities ?? []])
                    ->flatten()
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'room_id' => $room->id,
                    'room_type_id' => $roomType?->id,
                    'room_number' => $room->room_number,
                    'code' => $roomType?->code,
                    'name' => trim(($roomType?->name ?? 'Room').' #'.$room->room_number),
                    'description' => $roomType?->description,
                    'base_rate' => $room->daily_rate ?? $roomType?->base_rate,
                    'amenities' => $amenities,
                    'photos' => $room->photos ?? $roomType?->photos ?? [],
                    'photo_url' => Room::photoUrl($primaryPhoto),
                    'available_rooms' => 1,
                    'max_adults' => $roomType?->max_adults,
                    'hot' => false,
                ];
            })
            ->filter(function (array $row) use ($amenityNeedle) {
                if ($amenityNeedle === '') {
                    return true;
                }

                return collect($row['amenities'])
                    ->contains(fn ($item) => strtolower((string) $item) === $amenityNeedle);
            })
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function amenityOptions()
    {
        $roomTypeAmenities = RoomType::query()->pluck('amenities');
        $roomAmenities = Room::query()->pluck('amenities');

        return collect([$roomTypeAmenities, $roomAmenities])
            ->flatten()
            ->flatten()
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique(fn (string $item) => strtolower($item))
            ->sort(fn (string $a, string $b) => strcasecmp($a, $b))
            ->values();
    }
}
