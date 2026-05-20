<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Actions\CancelReservation;
use App\Domain\HBMS\Actions\CreateReservation;
use App\Domain\HBMS\Actions\UpdateReservation;
use App\Domain\HBMS\Models\Guest;
use App\Domain\HBMS\Models\RatePlan;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HBMS\CreateReservationRequest;
use App\Http\Requests\Api\V1\HBMS\UpdateReservationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status');

        $reservations = Reservation::query()
            ->with(['guest', 'room', 'roomType'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when(
                $request->filled('check_in_date'),
                fn ($q) => $q->whereDate('check_in_date', $request->input('check_in_date'))
            )
            ->latest('check_in_date')
            ->paginate(20)
            ->withQueryString();

        $statusCounts = Reservation::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('hbms.reservations.index', compact('reservations', 'status', 'statusCounts'));
    }

    public function create(): View
    {
        return view('hbms.reservations.form', [
            'reservation' => new Reservation(['status' => 'confirmed', 'adults' => 2, 'children' => 0]),
            'guests' => Guest::query()->orderBy('last_name')->get(),
            'roomTypes' => RoomType::query()->orderBy('name')->get(),
            'rooms' => Room::query()->with('roomType')->orderBy('room_number')->get(),
            'ratePlans' => RatePlan::query()->where('is_active', true)->get(),
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
        $reservation->load(['guest', 'room', 'roomType', 'ratePlan', 'folio.transactions']);

        $folioBalance = $reservation->folio
            ? app(\App\Domain\Shared\Services\FolioService::class)->balance($reservation->folio)
            : null;

        return view('hbms.reservations.show', compact('reservation', 'folioBalance'));
    }

    public function edit(Reservation $reservation): View
    {
        return view('hbms.reservations.form', [
            'reservation' => $reservation,
            'guests' => Guest::query()->orderBy('last_name')->get(),
            'roomTypes' => RoomType::query()->orderBy('name')->get(),
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
        try {
            app(CancelReservation::class)->execute($reservation);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.reservations.index')
            ->with('success', 'Reservation cancelled.');
    }
}
