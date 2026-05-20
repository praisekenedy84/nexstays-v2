<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomDamage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreRoomDamageRequest;
use App\Http\Requests\Web\UpdateRoomDamageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RoomDamageController extends Controller
{
    public function index(Request $request): View
    {
        $damages = RoomDamage::query()
            ->with(['room', 'reservation.guest', 'reporter'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('modules.damage.index', compact('damages'));
    }

    public function create(): View
    {
        return view('modules.damage.form', [
            'damage' => new RoomDamage,
            'rooms' => Room::query()->with('roomType')->orderBy('room_number')->get(),
            'reservations' => Reservation::query()
                ->with('guest')
                ->whereIn('status', ['checked_in', 'checked_out'])
                ->latest('check_out_date')
                ->limit(50)
                ->get(),
        ]);
    }

    public function store(StoreRoomDamageRequest $request): RedirectResponse
    {
        RoomDamage::query()->create([
            ...$request->validated(),
            'currency' => config('nexstay.currency.default', 'TZS'),
            'reported_by' => Auth::id(),
        ]);

        return redirect()
            ->route('tenant.damages.index')
            ->with('success', 'Damage report filed.');
    }

    public function edit(RoomDamage $damage): View
    {
        $damage->load(['room', 'reservation.guest']);

        return view('modules.damage.form', [
            'damage' => $damage,
            'rooms' => Room::query()->with('roomType')->orderBy('room_number')->get(),
            'reservations' => Reservation::query()
                ->with('guest')
                ->whereIn('status', ['checked_in', 'checked_out', 'confirmed'])
                ->latest('check_in_date')
                ->limit(50)
                ->get(),
        ]);
    }

    public function update(UpdateRoomDamageRequest $request, RoomDamage $damage): RedirectResponse
    {
        $data = $request->validated();
        if ($data['status'] === 'resolved' && ! $damage->resolved_at) {
            $data['resolved_at'] = now();
        }
        $damage->update($data);

        return redirect()->route('tenant.damages.index')->with('success', 'Damage report updated.');
    }

    public function destroy(RoomDamage $damage): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-damage'), 403);

        $damage->delete();

        return redirect()->route('tenant.damages.index')->with('success', 'Damage report removed.');
    }

    public function resolve(RoomDamage $damage): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-damage'), 403);

        $damage->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Damage marked resolved.');
    }
}
