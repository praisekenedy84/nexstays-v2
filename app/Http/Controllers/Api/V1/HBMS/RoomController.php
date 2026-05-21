<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\HBMS;

use App\Domain\HBMS\Models\Room;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\HBMS\UpdateRoomRequest;
use App\Http\Requests\Api\V1\HBMS\UpdateRoomStatusRequest;
use App\Http\Resources\Api\V1\RoomResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    use RespondsWithJsonApi;

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-rooms'), 403);

        $query = Room::query()
            ->with('roomType')
            ->when(
                $request->filled('filter.status'),
                fn ($q) => $q->where('status', $request->input('filter.status'))
            )
            ->when(
                $request->filled('filter.room_type_id'),
                fn ($q) => $q->where('room_type_id', $request->input('filter.room_type_id'))
            )
            ->orderBy('floor')
            ->orderBy('room_number');

        return $this->respondCollection(
            RoomResource::collection($query->paginate(min((int) $request->query('per_page', 50), 100)))
        );
    }

    public function show(Request $request, Room $room): JsonResponse
    {
        abort_unless($request->user()?->can('view-rooms'), 403);

        $room->load('roomType');

        return $this->respond(RoomResource::make($room));
    }

    public function updateStatus(UpdateRoomStatusRequest $request, Room $room): JsonResponse
    {
        $room->update(['status' => $request->validated('status')]);

        return $this->respond(RoomResource::make($room->fresh()->load('roomType')));
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $validated = $request->validated();
        $disk = Room::photosDisk();

        $existingPhotos = collect($room->photos ?? []);
        $removePhotos = collect($request->input('remove_photos', []))
            ->filter(fn ($path) => is_string($path) && $existingPhotos->contains($path))
            ->values();

        $removePhotos->each(fn (string $path) => Storage::disk($disk)->delete($path));

        $keptPhotos = $existingPhotos->reject(fn (string $path) => $removePhotos->contains($path))->values()->all();
        $uploadedPhotos = collect($request->file('photos', []))
            ->filter(fn ($file): bool => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file): string => $file->store('rooms/photos', $disk))
            ->values()
            ->all();

        $validated['photos'] = array_values(array_merge($keptPhotos, $uploadedPhotos));

        $room->update($validated);

        return $this->respond(RoomResource::make($room->fresh()->load('roomType')));
    }
}
