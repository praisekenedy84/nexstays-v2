<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\HBMS;

use App\Domain\HBMS\Models\Room;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\HBMS\UpdateRoomStatusRequest;
use App\Http\Resources\Api\V1\RoomResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
