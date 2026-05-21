<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreRoomRequest;
use App\Http\Requests\Web\UpdateRoomRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        ]);
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['photos'] = $this->storePhotos($request->file('photos', []));

        Room::query()->create($validated);

        return redirect()->route('tenant.rooms.index')->with('success', 'Room created.');
    }

    public function edit(Room $room): View
    {
        $room->load('roomType');

        return view('hbms.rooms.form', [
            'room' => $room,
            'roomTypes' => RoomType::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $validated = $request->validated();
        $disk = Room::photosDisk();

        $existingPhotos = collect($room->photos ?? []);
        $removePhotos = collect($request->input('remove_photos', []))
            ->filter(fn ($path) => is_string($path) && $existingPhotos->contains($path))
            ->values();

        $removePhotos->each(fn (string $path) => Storage::disk($disk)->delete($path));

        $keptPhotos = $existingPhotos->reject(fn (string $path) => $removePhotos->contains($path))->values()->all();
        $uploadedPhotos = $this->storePhotos($request->file('photos', []));

        $validated['photos'] = array_values(array_merge($keptPhotos, $uploadedPhotos));

        $room->update($validated);

        return redirect()->route('tenant.rooms.index')->with('success', "Room {$room->room_number} updated.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-rooms'), 403);
        abort_if(
            $room->reservations()->whereIn('status', ['confirmed', 'checked_in'])->exists(),
            403,
            'Room has active reservations.'
        );

        collect($room->photos ?? [])->each(
            fn (string $path) => Storage::disk(Room::photosDisk())->delete($path)
        );

        $room->delete();

        return redirect()->route('tenant.rooms.index')->with('success', 'Room removed.');
    }

    /**
     * @param array<int, UploadedFile> $photos
     * @return array<int, string>
     */
    private function storePhotos(array $photos): array
    {
        $disk = Room::photosDisk();

        return collect($photos)
            ->filter(fn ($file): bool => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file): string => $file->store('rooms/photos', $disk))
            ->values()
            ->all();
    }
}
