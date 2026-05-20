<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Reservation;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today();

        $todayArrivals = Reservation::query()
            ->whereDate('check_in_date', $today)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();

        $todayDepartures = Reservation::query()
            ->whereDate('check_out_date', $today)
            ->whereIn('status', ['checked_in', 'confirmed'])
            ->count();

        $totalBooked = Reservation::query()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();

        $upcomingArrivals = Reservation::query()
            ->with(['guest', 'room'])
            ->whereIn('status', ['confirmed'])
            ->whereDate('check_in_date', '>=', $today)
            ->orderBy('check_in_date')
            ->limit(5)
            ->get();

        $lastReservation = Reservation::query()
            ->with(['guest', 'roomType'])
            ->latest()
            ->first();

        return view('hbms.dashboard', compact(
            'todayArrivals',
            'todayDepartures',
            'totalBooked',
            'upcomingArrivals',
            'lastReservation',
        ));
    }
}
