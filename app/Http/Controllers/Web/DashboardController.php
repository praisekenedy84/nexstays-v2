<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Services\DivisionSalesService;
use App\Http\Controllers\Controller;
use App\Support\TenantFeatures;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const FB_SCOPES = ['both', 'restaurant', 'bar'];

    public function __construct(
        private readonly DivisionSalesService $divisionSales,
    ) {}

    public function __invoke(Request $request): View
    {
        $today = now()->startOfDay();
        $todayStr = $today->toDateString();

        $showHotelSummary = TenantFeatures::allows('hotel');
        $showFbSummary = TenantFeatures::allowsAny(
            config('nexstay.fb_feature_keys', ['restaurant', 'bar', 'lounge'])
        );

        $fbScope = $this->resolveFbScope($request);

        $todayArrivals = 0;
        $todayDepartures = 0;
        $totalBooked = 0;
        $upcomingArrivals = collect();
        $lastReservation = null;
        $todayBookedRooms = ['revenue' => 0.0, 'reservation_count' => 0, 'room_nights' => 0];
        $mtdBookedRooms = ['revenue' => 0.0, 'reservation_count' => 0, 'room_nights' => 0];

        if ($showHotelSummary) {
            $hotelKpis = Reservation::query()
                ->selectRaw("
                    SUM(CASE WHEN check_in_date = ? AND status IN ('confirmed', 'checked_in') THEN 1 ELSE 0 END) AS today_arrivals,
                    SUM(CASE WHEN check_out_date = ? AND status IN ('checked_in', 'confirmed') THEN 1 ELSE 0 END) AS today_departures,
                    SUM(CASE WHEN status IN ('confirmed', 'checked_in') THEN 1 ELSE 0 END) AS total_booked
                ", [$todayStr, $todayStr])
                ->first();

            $todayArrivals = (int) ($hotelKpis->today_arrivals ?? 0);
            $todayDepartures = (int) ($hotelKpis->today_departures ?? 0);
            $totalBooked = (int) ($hotelKpis->total_booked ?? 0);

            $upcomingArrivals = Reservation::query()
                ->select([
                    'id',
                    'guest_id',
                    'room_id',
                    'status',
                    'check_in_date',
                    'check_out_date',
                ])
                ->with([
                    'guest:id,first_name,last_name',
                    'room:id,room_number',
                ])
                ->where('status', 'confirmed')
                ->where('check_in_date', '>=', $todayStr)
                ->orderBy('check_in_date')
                ->limit(5)
                ->get();

            $lastReservation = Reservation::query()
                ->select([
                    'id',
                    'guest_id',
                    'room_type_id',
                    'booking_ref',
                    'status',
                    'check_in_date',
                    'check_out_date',
                    'created_at',
                ])
                ->with([
                    'guest:id,first_name,last_name',
                    'roomType:id,name',
                ])
                ->latest()
                ->first();

            $todayBookedRooms = $this->divisionSales->todayArrivalBookedRevenue();
            $mtdBookedRooms = $this->divisionSales->bookedRoomRevenue(
                $today->copy()->startOfMonth(),
                now()->endOfDay(),
            );
        }

        $todaySales = ['rooms' => 0.0, 'restaurant' => 0.0, 'bar' => 0.0, 'ancillary' => 0.0, 'total' => 0.0, 'room_nights' => 0, 'payments_collected' => 0.0];
        $mtdSales = ['rooms' => 0.0, 'restaurant' => 0.0, 'bar' => 0.0, 'ancillary' => 0.0, 'total' => 0.0, 'room_nights' => 0, 'payments_collected' => 0.0];
        $recentSnapshots = collect();
        $revenueTrend = [];
        $trendFrom = $today->copy()->subDays(29);
        $trendTo = $today->copy();
        $trendIsHourly = false;

        if ($showHotelSummary || $showFbSummary) {
            $todaySales = $this->divisionSales->liveSummary();
            $mtdSales = $this->divisionSales->mtdSummary();
            $recentSnapshots = $this->divisionSales->recentSnapshots(7);

            [$trendFrom, $trendTo] = $this->resolveTrendRange($request);
            $trendIsHourly = $this->divisionSales->revenueTrendIsHourly($trendFrom, $trendTo);
            $revenueTrend = $this->divisionSales->revenueTrend(
                $trendFrom,
                $trendTo,
                $trendIsHourly ? null : $todaySales,
            );
        }

        $topProducts = new Collection;
        $todayFbOrders = 0;
        $fbOutletTypes = $this->fbOutletTypes($fbScope);

        if ($showFbSummary) {
            $qtyCast = DB::connection()->getDriverName() === 'pgsql'
                ? 'SUM(order_items.quantity)::integer'
                : 'CAST(SUM(order_items.quantity) AS INTEGER)';

            $topProducts = OrderItem::query()
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('outlets', 'orders.outlet_id', '=', 'outlets.id')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                ->where('orders.status', 'closed')
                ->whereNotNull('orders.closed_at')
                ->whereBetween('orders.closed_at', [now()->startOfMonth(), now()->endOfDay()])
                ->whereIn('outlets.type', $fbOutletTypes)
                ->where('order_items.status', '!=', 'voided')
                ->selectRaw("menu_items.name AS item_name, {$qtyCast} AS qty_sold, SUM(order_items.quantity * order_items.unit_price) AS revenue")
                ->groupBy('menu_items.id', 'menu_items.name')
                ->orderByDesc('qty_sold')
                ->limit(8)
                ->get();

            $todayFbOrders = Order::query()
                ->join('outlets', 'orders.outlet_id', '=', 'outlets.id')
                ->where('orders.status', 'closed')
                ->whereBetween('orders.closed_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->whereIn('outlets.type', $fbOutletTypes)
                ->count();
        }

        $fbRevenueTrend = array_map(
            fn (array $point): float => $this->fbAmountFromPoint($point, $fbScope),
            $revenueTrend
        );

        $hotelTrendValues = $showFbSummary
            ? array_map(
                fn (array $point): float => (float) $point['rooms'] + (float) $point['ancillary'],
                $revenueTrend
            )
            : array_map(fn (array $point): float => (float) $point['total'], $revenueTrend);

        $hotelTrendTotal = array_sum($hotelTrendValues);
        $fbTrendTotal = array_sum($fbRevenueTrend);

        $fbTodayTotal = $this->fbAmountFromSummary($todaySales, $fbScope);
        $fbMtdTotal = $this->fbAmountFromSummary($mtdSales, $fbScope);

        return view('hbms.dashboard', compact(
            'showHotelSummary',
            'showFbSummary',
            'fbScope',
            'todayArrivals',
            'todayDepartures',
            'totalBooked',
            'upcomingArrivals',
            'lastReservation',
            'todaySales',
            'mtdSales',
            'todayBookedRooms',
            'mtdBookedRooms',
            'recentSnapshots',
            'revenueTrend',
            'fbRevenueTrend',
            'hotelTrendValues',
            'hotelTrendTotal',
            'fbTrendTotal',
            'fbTodayTotal',
            'fbMtdTotal',
            'trendFrom',
            'trendTo',
            'trendIsHourly',
            'topProducts',
            'todayFbOrders',
        ));
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveTrendRange(Request $request): array
    {
        $today = now()->startOfDay();

        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->input('from'))->startOfDay();
            $to = Carbon::parse($request->input('to'))->startOfDay();
        } else {
            $from = $today->copy()->subDays(29);
            $to = $today->copy();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($to->gt($today)) {
            $to = $today->copy();
        }

        if ($from->gt($to)) {
            $from = $to->copy();
        }

        return [$from, $to];
    }

    private function resolveFbScope(Request $request): string
    {
        $scope = (string) $request->input('fb', 'both');

        return in_array($scope, self::FB_SCOPES, true) ? $scope : 'both';
    }

    /**
     * @return list<string>
     */
    private function fbOutletTypes(string $scope): array
    {
        return match ($scope) {
            'restaurant' => ['restaurant', 'lounge'],
            'bar' => ['bar'],
            default => ['restaurant', 'bar', 'lounge'],
        };
    }

    /**
     * @param  array{restaurant: float|int|string, bar: float|int|string}  $point
     */
    private function fbAmountFromPoint(array $point, string $scope): float
    {
        $restaurant = (float) $point['restaurant'];
        $bar = (float) $point['bar'];

        return match ($scope) {
            'restaurant' => $restaurant,
            'bar' => $bar,
            default => $restaurant + $bar,
        };
    }

    /**
     * @param  array{restaurant: float, bar: float}  $summary
     */
    private function fbAmountFromSummary(array $summary, string $scope): float
    {
        return $this->fbAmountFromPoint($summary, $scope);
    }
}
