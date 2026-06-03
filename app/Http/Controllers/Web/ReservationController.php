<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Actions\CancelReservation;
use App\Domain\HBMS\Actions\CheckInGuest;
use App\Domain\HBMS\Actions\CheckOutGuest;
use App\Domain\HBMS\Actions\CreateReservation;
use App\Domain\HBMS\Actions\PostOverstayCharge;
use App\Domain\HBMS\Actions\SettleOverstay;
use App\Domain\HBMS\Actions\UpdateReservation;
use App\Domain\HBMS\Models\Guest;
use App\Domain\HBMS\Models\RatePlan;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Services\ReservationSettingsService;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\PaymentMethodSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HBMS\CreateReservationRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use App\Http\Requests\Web\PostOverstayChargeRequest;
use App\Http\Requests\Web\SettleOverstayRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status');
        $sort   = in_array($request->query('sort'), ['booking_ref', 'check_in_date', 'check_out_date', 'created_at']) ? $request->query('sort') : 'check_in_date';
        $dir    = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));

        $reservations = Reservation::query()
            ->with(['guest', 'room', 'roomType'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('check_in_date'), fn ($q) => $q->whereDate('check_in_date', $request->input('check_in_date')))
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('booking_ref', 'ilike', "%{$search}%")
                ->orWhereHas('guest', fn ($gq) => $gq
                    ->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%"))))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        $statusCounts = Reservation::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('hbms.reservations.index', compact('reservations', 'status', 'statusCounts', 'sort', 'dir', 'search'));
    }

    public function create(): View
    {
        return view('hbms.reservations.form', [
            'reservation' => new Reservation(['status' => 'confirmed', 'adults' => 2, 'children' => 0]),
            'guests' => Guest::query()->orderBy('last_name')->get(),
            'rooms' => Room::query()->with('roomType')->orderBy('room_number')->get(),
            'ratePlans' => RatePlan::query()->where('is_active', true)->get(),
        ]);
    }

    public function availableRooms(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('manage-reservations'), 403);

        $validated = validator($request->all(), [
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'exclude_reservation_id' => ['nullable', 'uuid', 'exists:reservations,id'],
        ])->validate();

        $reservedRoomIds = Reservation::query()
            ->whereIn('status', ['inquiry', 'confirmed', 'checked_in'])
            ->when(
                ! empty($validated['exclude_reservation_id']),
                fn ($query) => $query->whereKeyNot($validated['exclude_reservation_id'])
            )
            ->where('check_in_date', '<', $validated['check_out_date'])
            ->where('check_out_date', '>', $validated['check_in_date'])
            ->pluck('room_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $rooms = Room::query()
            ->with('roomType')
            ->orderBy('room_number')
            ->get()
            ->map(function (Room $room) use ($reservedRoomIds): array {
                $status = (string) $room->status;
                $isReservedForDates = in_array($room->id, $reservedRoomIds, true);
                $isBookableByStatus = ! in_array($status, ['out_of_order', 'blocked'], true);
                $isAvailable = $isBookableByStatus && ! $isReservedForDates;

                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'room_type_name' => $room->roomType?->name,
                    'status' => $status,
                    'daily_rate' => (string) ($room->daily_rate ?? 0),
                    'is_available' => $isAvailable,
                    'reason' => $isReservedForDates ? 'reserved_for_selected_dates' : (! $isBookableByStatus ? 'room_status_unavailable' : null),
                ];
            });

        return response()->json([
            'data' => $rooms->values()->all(),
        ]);
    }

    public function store(CreateReservationRequest $request): RedirectResponse
    {
        $reservation = app(CreateReservation::class)->execute($request->validated());

        return redirect()
            ->route('tenant.reservations.show', $reservation)
            ->with('success', 'Reservation created.');
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load([
            'guest',
            'room',
            'roomType',
            'ratePlan',
            'overstayChargeTransaction',
            'overstaySettlementTransaction',
            'overstaySettlementPayment',
            'overstaySettledBy',
            'folio.transactions' => fn ($q) => $q->orderByDesc('posted_at'),
        ]);

        $folioService    = app(FolioService::class);
        $folioBalance    = $reservation->folio ? $folioService->balance($reservation->folio) : null;
        $enabledMethods  = app(PaymentMethodSettingsService::class)->enabledMethods();
        $paymentMethods  = PaymentMethodSettingsService::METHODS;
        $overstayIncrease = $reservation->overstayIncrease();

        return view('hbms.reservations.show', compact(
            'reservation',
            'folioBalance',
            'enabledMethods',
            'paymentMethods',
            'overstayIncrease',
        ));
    }

    public function ticket(Reservation $reservation): Response
    {
        $reservation->loadMissing(['guest', 'room.roomType', 'roomType', 'ratePlan']);

        $paymentMode = app(ReservationSettingsService::class)->all()['payment_mode'] ?? 'prepaid';

        $qrOptions  = new QROptions(['outputType' => 'svg', 'imageBase64' => false]);
        $qrSvgB64  = base64_encode((new QRCode($qrOptions))->render($reservation->booking_ref));

        $filename = sprintf(
            'reservation-ticket-%s.pdf',
            Str::of($reservation->booking_ref)->slug()->value()
        );

        return Pdf::loadView('hbms.reservations.ticket', [
            'reservation' => $reservation,
            'paymentMode' => $paymentMode,
            'generatedAt' => now(),
            'qrSvgB64'    => $qrSvgB64,
        ])
            ->setPaper('a5')
            ->download($filename);
    }

    public function edit(Reservation $reservation): View
    {
        return view('hbms.reservations.form', [
            'reservation' => $reservation,
            'guests' => Guest::query()->orderBy('last_name')->get(),
            'rooms' => Room::query()->with('roomType')->orderBy('room_number')->get(),
            'ratePlans' => RatePlan::query()->where('is_active', true)->get(),
        ]);
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $reservation = app(UpdateReservation::class)->execute($reservation, $request->validated());

        return redirect()
            ->route('tenant.reservations.show', $reservation)
            ->with('success', 'Reservation updated.');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-reservations'), 403);

        try {
            $this->deleteReservationPermanently($reservation);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.reservations.index')
            ->with('success', 'Reservation deleted permanently.');
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-reservations'), 403);

        try {
            app(CancelReservation::class)->execute($reservation);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.reservations.index')
            ->with('success', 'Reservation cancelled.');
    }

    public function checkIn(Reservation $reservation): RedirectResponse
    {
        abort_unless(auth()->user()?->can('check-in-guests'), 403);

        try {
            app(CheckInGuest::class)->execute($reservation);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.reservations.show', $reservation)
            ->with('success', "Guest checked in — {$reservation->booking_ref}.");
    }

    public function checkOut(Reservation $reservation): RedirectResponse
    {
        abort_unless(auth()->user()?->can('check-out-guests'), 403);

        $reservation->loadMissing('folio');

        try {
            app(CheckOutGuest::class)->execute($reservation);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.reservations.show', $reservation)
            ->with('success', "Guest checked out — {$reservation->booking_ref}.");
    }

    public function postOverstayCharge(PostOverstayChargeRequest $request, Reservation $reservation): RedirectResponse
    {
        $validated = $request->validated();

        try {
            app(PostOverstayCharge::class)->execute(
                $reservation,
                $validated['notes'] ?? null,
                isset($validated['rate_override']) ? (float) $validated['rate_override'] : null,
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.reservations.show', $reservation)
            ->with('success', 'Overstay charge posted to folio. Collect payment or waive to complete settlement.');
    }

    public function settleOverstay(SettleOverstayRequest $request, Reservation $reservation): RedirectResponse
    {
        $validated = $request->validated();

        try {
            app(SettleOverstay::class)->execute(
                reservation: $reservation,
                settlement: $validated['settlement'],
                method: $validated['method'] ?? null,
                waiverReason: $validated['waiver_reason'] ?? null,
                notes: $validated['notes'] ?? null,
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $label = $validated['settlement'] === 'paid'
            ? 'Overstay charge paid and settled.'
            : 'Overstay charge waived.';

        return redirect()
            ->route('tenant.reservations.show', $reservation)
            ->with('success', $label);
    }

    public function noShow(Reservation $reservation): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-reservations'), 403);

        if (! in_array($reservation->status, ['confirmed', 'inquiry'], true)) {
            return back()->with('error', 'Only confirmed or inquiry reservations can be marked as no-show.');
        }

        $reservation->update(['status' => 'no_show']);

        if ($reservation->room !== null) {
            $reservation->room->update(['status' => 'vacant_clean']);
        }

        return back()->with('success', "Marked as no-show — {$reservation->booking_ref}.");
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-reservations'), 403);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:cancel,delete'],
            'reservation_ids' => ['required', 'array', 'min:1'],
            'reservation_ids.*' => ['required', 'uuid', 'exists:reservations,id'],
        ]);

        $reservations = Reservation::query()
            ->whereIn('id', $validated['reservation_ids'])
            ->get()
            ->keyBy('id');

        $success = 0;
        $errors = [];

        foreach ($validated['reservation_ids'] as $reservationId) {
            /** @var Reservation|null $reservation */
            $reservation = $reservations->get($reservationId);

            if ($reservation === null) {
                continue;
            }

            try {
                if ($validated['action'] === 'cancel') {
                    app(CancelReservation::class)->execute($reservation);
                } else {
                    $this->deleteReservationPermanently($reservation);
                }

                $success++;
            } catch (\DomainException $e) {
                $errors[] = "{$reservation->booking_ref}: {$e->getMessage()}";
            }
        }

        if ($success === 0) {
            return back()->with('error', $errors[0] ?? 'No reservations were updated.');
        }

        $actionLabel = $validated['action'] === 'cancel' ? 'cancelled' : 'deleted permanently';
        $message = "{$success} reservation(s) {$actionLabel}.";

        if ($errors !== []) {
            $message .= ' Some failed: '.implode(' | ', array_slice($errors, 0, 3));
        }

        return redirect()
            ->route('tenant.reservations.index')
            ->with('success', $message);
    }

    private function deleteReservationPermanently(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $reservation->loadMissing(['room', 'folio.transactions']);

            throw_if(
                in_array($reservation->status, ['checked_in', 'checked_out'], true),
                \DomainException::class,
                'Checked-in or checked-out reservations cannot be permanently deleted.'
            );

            $folio = $reservation->folio;

            if ($folio !== null) {
                throw_if(
                    $folio->transactions()->exists(),
                    \DomainException::class,
                    'Reservation has folio transactions and cannot be permanently deleted.'
                );

                $folio->forceDelete();
            }

            if (
                $reservation->room !== null
                && in_array($reservation->room->status, ['blocked', 'occupied'], true)
            ) {
                $reservation->room->update(['status' => 'vacant_clean']);
            }

            $reservation->forceDelete();
        });
    }
}
