<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Services\DivisionSalesService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DivisionSalesService $divisionSales,
    ) {}

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

        $todaySales      = $this->divisionSales->liveSummary();
        $mtdSales        = $this->divisionSales->mtdSummary();
        $recentSnapshots = $this->divisionSales->recentSnapshots(7);

        $topProducts = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('outlets', 'orders.outlet_id', '=', 'outlets.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->where('orders.status', 'closed')
            ->whereNotNull('orders.closed_at')
            ->whereBetween('orders.closed_at', [now()->startOfMonth(), now()->endOfDay()])
            ->whereIn('outlets.type', ['restaurant', 'bar', 'lounge'])
            ->where('order_items.status', '!=', 'voided')
            ->selectRaw('menu_items.name AS item_name, SUM(order_items.quantity)::integer AS qty_sold, SUM(order_items.quantity * order_items.unit_price) AS revenue')
            ->groupBy('menu_items.id', 'menu_items.name')
            ->orderByDesc('qty_sold')
            ->limit(8)
            ->get();

        $todayFbOrders = Order::query()
            ->join('outlets', 'orders.outlet_id', '=', 'outlets.id')
            ->where('orders.status', 'closed')
            ->whereDate('orders.closed_at', $today)
            ->whereIn('outlets.type', ['restaurant', 'bar', 'lounge'])
            ->count();

        return view('hbms.dashboard', compact(
            'todayArrivals',
            'todayDepartures',
            'totalBooked',
            'upcomingArrivals',
            'lastReservation',
            'todaySales',
            'mtdSales',
            'recentSnapshots',
            'topProducts',
            'todayFbOrders',
        ));
    }
}
