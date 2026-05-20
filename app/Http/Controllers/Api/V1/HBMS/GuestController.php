<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\HBMS;

use App\Domain\HBMS\Models\Guest;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\HBMS\StoreGuestRequest;
use App\Http\Requests\Api\V1\HBMS\UpdateGuestRequest;
use App\Http\Resources\Api\V1\GuestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    use RespondsWithJsonApi;

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-guests'), 403);

        $query = Guest::query()
            ->when(
                $request->filled('filter.search'),
                function ($q) use ($request) {
                    $term = '%'.mb_strtolower((string) $request->input('filter.search')).'%';
                    $q->where(function ($inner) use ($term) {
                        $inner->whereRaw('LOWER(first_name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(last_name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(phone) LIKE ?', [$term]);
                    });
                }
            )
            ->orderBy('last_name')
            ->orderBy('first_name');

        return $this->respondCollection(
            GuestResource::collection($query->paginate(min((int) $request->query('per_page', 25), 100)))
        );
    }

    public function store(StoreGuestRequest $request): JsonResponse
    {
        $guest = Guest::query()->create($request->validated());

        return $this->respond(GuestResource::make($guest), 201);
    }

    public function show(Request $request, Guest $guest): JsonResponse
    {
        abort_unless($request->user()?->can('view-guests'), 403);

        $guest->loadCount('reservations');

        return $this->respond(GuestResource::make($guest));
    }

    public function update(UpdateGuestRequest $request, Guest $guest): JsonResponse
    {
        $guest->update($request->validated());

        return $this->respond(GuestResource::make($guest->fresh()));
    }
}
