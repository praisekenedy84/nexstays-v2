<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\Order;
use App\Domain\Till\Models\Payment;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShiftSummaryController extends Controller
{
    public function mine(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : now()->toDateString();

        $from = Carbon::parse($date)->startOfDay();
        $to   = Carbon::parse($date)->endOfDay();

        $baseQuery = Order::query()
            ->where('waiter_id', Auth::id())
            ->whereIn('status', ['closed', 'open', 'sent_to_kitchen', 'partially_served', 'served'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('opened_at', [$from, $to])
                  ->orWhereBetween('closed_at', [$from, $to]);
            })
            ->latest('opened_at');

        // Summary always uses the full day's orders
        $allOrders = (clone $baseQuery)->with(['items.menuItem'])->get();
        $summary   = $this->buildSummary($allOrders);

        // Table display is paginated
        $orders = (clone $baseQuery)->with(['outlet', 'table', 'items.menuItem'])->paginate(30)->withQueryString();

        return view('modules.pos.shift', compact('orders', 'summary', 'date'));
    }

    public function all(Request $request): View
    {
        abort_unless(auth()->user()?->can('view-fb-reports'), 403);

        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : now()->toDateString();

        $from = Carbon::parse($date)->startOfDay();
        $to   = Carbon::parse($date)->endOfDay();

        $orders = Order::query()
            ->with(['outlet', 'table', 'items.menuItem', 'waiter'])
            ->whereIn('status', ['closed', 'open', 'sent_to_kitchen', 'partially_served', 'served'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('opened_at', [$from, $to])
                  ->orWhereBetween('closed_at', [$from, $to]);
            })
            ->latest('opened_at')
            ->get();

        // Group by waiter
        $byWaiter = $orders->groupBy('waiter_id')->map(function ($waiterOrders) {
            return [
                'waiter'  => $waiterOrders->first()?->waiter,
                'orders'  => $waiterOrders,
                'summary' => $this->buildSummary($waiterOrders),
            ];
        })->sortBy(fn ($g) => $g['waiter']?->name ?? '');

        $overall = $this->buildSummary($orders);

        return view('modules.pos.shift-all', compact('byWaiter', 'overall', 'date'));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array{cash: float, folio: float, total_closed: float, count_open: int, count_closed: int}
     */
    private function buildSummary(\Illuminate\Support\Collection $orders): array
    {
        $closedOrderIds = $orders->where('status', 'closed')->pluck('id');

        $cashTotal = (float) Payment::query()
            ->whereIn('order_id', $closedOrderIds)
            ->whereNull('folio_id')
            ->sum('amount');

        $folioTotal = (float) $orders
            ->where('status', 'closed')
            ->filter(fn ($o) => $o->folio_id !== null)
            ->sum('total');

        return [
            'cash'          => $cashTotal,
            'folio'         => $folioTotal,
            'total_closed'  => $cashTotal + $folioTotal,
            'count_open'    => $orders->where('status', '!=', 'closed')->count(),
            'count_closed'  => $orders->where('status', 'closed')->count(),
        ];
    }
}
