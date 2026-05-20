<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HBMS\UpdateRoomStatusRequest;
use App\Http\Requests\Web\StoreRoomRequest;
use App\Http\Requests\Web\UpdateRoomRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status');
        $roomTypeId = $request->input('room_type_id');

        $rooms = Room::query()
            ->with('roomType')
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($roomTypeId, fn ($q) => $q->where('room_type_id', $roomTypeId))
            ->orderBy('floor')
            ->orderBy('room_number')
            ->paginate(50)
            ->withQueryString();

        $roomTypes = RoomType::query()->orderBy('name')->get();

        $statusCounts = Room::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('hbms.rooms.index', compact('rooms', 'status', 'roomTypeId', 'roomTypes', 'statusCounts'));
    }

    public function create(): View
    {
        return view('hbms.rooms.form', [
            'room' => new Room(['status' => 'vacant_clean']),
            'roomTypes' => RoomType::query()->orderBy('name')->get(),
            'fullEdit' => true,
        ]);
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        Room::query()->create($request->validated());

        return redirect()->route('tenant.rooms.index')->with('success', 'Room created.');
    }

    public function edit(Room $room): View
    {
        $room->load('roomType');
        $fullEdit = auth()->user()?->can('manage-rooms') ?? false;

        return view('hbms.rooms.form', [
            'room' => $room,
            'roomTypes' => RoomType::query()->orderBy('name')->get(),
            'fullEdit' => $fullEdit,
        ]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        if ($request->user()?->can('manage-rooms')) {
            $validated = $request->validate((new UpdateRoomRequest)->rules());
            $room->update($validated);

            return redirect()->route('tenant.rooms.index')->with('success', "Room {$room->room_number} updated.");
        }

        if ($request->user()?->can('manage-room-status')) {
            $validated = $request->validate((new UpdateRoomStatusRequest)->rules());
            $room->update(['status' => $validated['status']]);

            return redirect()->route('tenant.rooms.index')->with('success', "Room {$room->room_number} status updated.");
        }

        abort(403);
    }

    public function destroy(Room $room): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-rooms'), 403);
        abort_if(
            $room->reservations()->whereIn('status', ['confirmed', 'checked_in'])->exists(),
            403,
            'Room has active reservations.'
        );

        $room->delete();

        return redirect()->route('tenant.rooms.index')->with('success', 'Room removed.');
    }
}
