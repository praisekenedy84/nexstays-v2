<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\HBMS;

use App\Domain\HBMS\Actions\CancelReservation;
use App\Domain\HBMS\Actions\CheckInGuest;
use App\Domain\HBMS\Actions\CheckOutGuest;
use App\Domain\HBMS\Actions\CreateReservation;
use App\Domain\HBMS\Actions\PostOverstayCharge;
use App\Domain\HBMS\Actions\SettleOverstay;
use App\Domain\HBMS\Actions\UpdateReservation;
use App\Domain\HBMS\Models\Reservation;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\HBMS\CheckInReservationRequest;
use App\Http\Requests\Api\V1\HBMS\CheckOutReservationRequest;
use App\Http\Requests\Api\V1\HBMS\CreateReservationRequest;
use App\Http\Requests\Api\V1\HBMS\UpdateReservationRequest;
use App\Http\Resources\Api\V1\FolioResource;
use App\Http\Resources\Api\V1\ReservationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    use RespondsWithJsonApi;

    private const DEFAULT_INCLUDES = ['guest', 'room', 'roomType', 'ratePlan', 'folio'];

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-reservations'), 403);
        $query = Reservation::query()
            ->with(self::DEFAULT_INCLUDES)
            ->when(
                $request->filled('filter.status'),
                fn ($q) => $q->where('status', $request->input('filter.status'))
            )
            ->when(
                $request->filled('filter.check_in_date'),
                fn ($q) => $q->whereDate('check_in_date', $request->input('filter.check_in_date'))
            );

        $sort = ltrim((string) $request->query('sort', '-created_at'), '-');
        $direction = str_starts_with((string) $request->query('sort', '-created_at'), '-') ? 'desc' : 'asc';
        $allowedSorts = ['created_at', 'check_in_date', 'booking_ref'];

        if (in_array($sort, $allowedSorts, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $paginator = $query->paginate(
            min((int) $request->query('per_page', 25), 100)
        );

        return $this->respondCollection(
            ReservationResource::collection($paginator)
        );
    }

    public function store(CreateReservationRequest $request): JsonResponse
    {
        $reservation = app(CreateReservation::class)->execute($request->validated());
        $reservation->load(self::DEFAULT_INCLUDES);

        return $this->respond(
            ReservationResource::make($reservation),
            201
        );
    }

    public function show(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless($request->user()?->can('view-reservations'), 403);
        $reservation->load(self::DEFAULT_INCLUDES);

        return $this->respond(ReservationResource::make($reservation));
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $reservation = app(UpdateReservation::class)->execute($reservation, $request->validated());

        return $this->respond(ReservationResource::make($reservation));
    }

    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless($request->user()?->can('manage-reservations'), 403);
        $reservation = app(CancelReservation::class)->execute($reservation);
        $reservation->load(self::DEFAULT_INCLUDES);

        return $this->respond(ReservationResource::make($reservation));
    }

    public function checkIn(CheckInReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $folio = app(CheckInGuest::class)->execute($reservation, $request->validated());
        $reservation->refresh()->load(self::DEFAULT_INCLUDES);

        return $this->respond([
            'reservation' => ReservationResource::make($reservation),
            'folio' => FolioResource::make($folio),
        ]);
    }

    public function checkOut(CheckOutReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $reservation = app(CheckOutGuest::class)->execute($reservation);

        return $this->respond(ReservationResource::make($reservation));
    }

    public function postOverstayCharge(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless($request->user()?->can('manage-reservations'), 403);

        $validated = $request->validate([
            'rate_override' => ['nullable', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        app(PostOverstayCharge::class)->execute(
            $reservation,
            $validated['notes'] ?? null,
            isset($validated['rate_override']) ? (float) $validated['rate_override'] : null,
        );

        $reservation->refresh()->load(self::DEFAULT_INCLUDES);

        return $this->respond(ReservationResource::make($reservation));
    }

    public function settleOverstay(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless($request->user()?->can('post-folio-charges'), 403);

        $enabled = app(\App\Domain\Shared\Services\PaymentMethodSettingsService::class)->enabledMethods();

        $validated = $request->validate([
            'settlement'    => ['required', 'string', 'in:paid,waived'],
            'method'        => ['required_if:settlement,paid', 'nullable', 'string', 'in:'.implode(',', $enabled !== [] ? $enabled : ['__none__'])],
            'waiver_reason' => ['required_if:settlement,waived', 'nullable', 'string', 'max:500'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        app(SettleOverstay::class)->execute(
            reservation: $reservation,
            settlement: $validated['settlement'],
            method: $validated['method'] ?? null,
            waiverReason: $validated['waiver_reason'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        $reservation->refresh()->load(self::DEFAULT_INCLUDES);

        return $this->respond(ReservationResource::make($reservation));
    }
}
