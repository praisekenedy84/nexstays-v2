<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Reservation;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookedListController extends Controller
{
    public function index(Request $request): View
    {
        $from   = $request->filled('from') ? Carbon::parse($request->input('from')) : now()->startOfDay();
        $to     = $request->filled('to') ? Carbon::parse($request->input('to')) : now()->addDays(30)->endOfDay();
        $sort   = in_array($request->query('sort'), ['check_in_date', 'check_out_date', 'booking_ref', 'status']) ? $request->query('sort') : 'check_in_date';
        $dir    = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $dateField = in_array($request->query('date_field'), ['check_in_date', 'checked_in_at', 'check_out_date', 'checked_out_at'], true)
            ? $request->query('date_field')
            : 'check_in_date';

        $statuses = ['inquiry', 'confirmed', 'checked_in'];
        if (in_array($dateField, ['checked_in_at', 'checked_out_at', 'check_out_date'], true)) {
            $statuses[] = 'checked_out';
        }

        $bookings = Reservation::query()
            ->with(['guest', 'room', 'roomType'])
            ->whereIn('status', $statuses)
            ->when(
                $dateField === 'check_in_date',
                fn ($q) => $q->where(function ($inner) use ($from, $to) {
                    // Upcoming arrivals in date range OR currently in-house
                    $inner->whereBetween('check_in_date', [$from, $to])
                        ->orWhere('status', 'checked_in');
                }),
                fn ($q) => $q->whereBetween($dateField, [$from, $to]),
            )
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('booking_ref', 'ilike', "%{$search}%")
                ->orWhereHas('guest', fn ($gq) => $gq
                    ->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%"))))
            ->orderBy($sort, $dir)
            ->paginate(25)
            ->withQueryString();

        return view('modules.booked-list.index', compact('bookings', 'from', 'to', 'sort', 'dir', 'search', 'dateField'));
    }
}
