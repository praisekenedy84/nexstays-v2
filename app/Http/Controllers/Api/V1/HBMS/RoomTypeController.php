<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\HBMS;

use App\Domain\HBMS\Models\RoomType;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Resources\Api\V1\RoomTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    use RespondsWithJsonApi;

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-availability'), 403);

        $query = RoomType::query()
            ->withCount(['rooms'])
            ->orderBy('name');

        return $this->respondCollection(
            RoomTypeResource::collection($query->paginate(min((int) $request->query('per_page', 25), 100)))
        );
    }

    public function show(Request $request, RoomType $roomType): JsonResponse
    {
        abort_unless($request->user()?->can('view-availability'), 403);

        $roomType->loadCount('rooms');

        return $this->respond(RoomTypeResource::make($roomType));
    }
}
