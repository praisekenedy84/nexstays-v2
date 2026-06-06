<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Expenditures\Models\Expenditure;
use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Purchases\Models\PurchaseOrder;
use App\Domain\Shared\Models\MenuCategory;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Till\Models\Payment;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportingService
{
    public function __construct(
        private readonly FolioService $folioService,
        private readonly OrderRevenueService $orderRevenue,
    ) {}

    /**
     * @return Collection<int, array{folio: Folio, balance: Money, reservation: mixed}>
     */
    public function outstandingDebts(): Collection
    {
        return Folio::query()
            ->with(['reservation.guest', 'reservation.room'])
            ->whereHas('reservation')
            ->where('status', 'open')
            ->get()
            ->map(function (Folio $folio) {
                $balance = $this->folioService->balance($folio);

                return [
                    'folio' => $folio,
                    'balance' => $balance,
                    'reservation' => $folio->reservation,
                ];
            })
            ->filter(fn (array $row) => $row['balance']->isPositive())
            ->sortByDesc(fn (array $row) => $row['balance']->getAmount()->toFloat())
            ->values();
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function checkoutCountdown(): Collection
    {
        return Reservation::query()
            ->with(['guest', 'room', 'roomType'])
            ->where('status', 'checked_in')
            ->orderBy('check_out_date')
            ->orderBy('check_in_date')
            ->get();
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function bookedList(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from ??= now()->startOfDay();
        $to ??= now()->addDays(30)->endOfDay();

        return Reservation::query()
            ->with(['guest', 'room', 'roomType'])
            ->whereIn('status', ['confirmed', 'inquiry'])
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->orderBy('check_in_date')
            ->get();
    }

    /**
     * @return array{food: string, drinks: string, total: string, from: string, to: string}
     */
    public function fbRevenueSplit(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();

        // Folio-posted F&B charges (room charges via folio transactions)
        $folioRows = FolioTransaction::query()
            ->forReporting()
            ->whereNull('voided_at')
            ->whereBetween('posted_at', [$from, $to])
            ->whereIn('transaction_type', ['restaurant', 'bar', 'lounge'])
            ->selectRaw('transaction_type, SUM(amount) as total')
            ->groupBy('transaction_type')
            ->pluck('total', 'transaction_type');

        $directSplit = $this->orderRevenue->directPaymentRevenueSplit($from, $to);

        $food = (float) ($folioRows['restaurant'] ?? 0)
            + (float) ($folioRows['lounge'] ?? 0)
            + $directSplit['restaurant'];
        $drinks = (float) ($folioRows['bar'] ?? 0)
            + $directSplit['bar'];

        return [
            'food'   => (string) $food,
            'drinks' => (string) $drinks,
            'total'  => (string) ($food + $drinks),
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
        ];
    }

    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     total_reservations: int,
     *     room_nights: int,
     *     projected_room_revenue: string,
     *     deposits_collected: string,
     *     balance_expected_on_arrival: string,
     *     status_counts: array{
     *         inquiry: int,
     *         confirmed: int,
     *         checked_in: int,
     *         checked_out: int,
     *         cancelled: int,
     *         no_show: int
     *     }
     * }
     */
    public function roomReservationFinance(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();
        $paymentMode = app(\App\Domain\HBMS\Services\ReservationSettingsService::class)->all()['payment_mode'] ?? 'prepaid';

        $reservations = Reservation::query()
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->get([
                'status',
                'check_in_date',
                'check_out_date',
                'daily_rate',
                'deposit_amount',
                'cancellation_charge_amount',
            ]);

        $statusCounts = [
            'inquiry' => 0,
            'confirmed' => 0,
            'checked_in' => 0,
            'checked_out' => 0,
            'cancelled' => 0,
            'no_show' => 0,
        ];

        $currency = 'TZS';
        $projectedRevenue = Money::of('0', $currency);
        $depositsCollected = Money::of('0', $currency);
        $inquiryExpectedOnArrival = Money::of('0', $currency);
        $roomNights = 0;

        foreach ($reservations as $reservation) {
            if (isset($statusCounts[$reservation->status])) {
                $statusCounts[$reservation->status]++;
            }

            $nightCount = max(
                1,
                Carbon::parse($reservation->check_in_date)->diffInDays(Carbon::parse($reservation->check_out_date))
            );

            $roomNights += $nightCount;

            $reservationGross = Money::of((string) $reservation->daily_rate, $currency)->multipliedBy($nightCount);
            if ($reservation->status === 'cancelled' && $reservation->cancellation_charge_amount !== null) {
                $reservationGross = Money::of((string) $reservation->cancellation_charge_amount, $currency);
            }

            if ($reservation->status === 'inquiry') {
                $inquiryExpectedOnArrival = $inquiryExpectedOnArrival->plus($reservationGross);
                continue;
            }

            $projectedRevenue = $projectedRevenue->plus($reservationGross);
            if ($paymentMode === 'prepaid') {
                $depositsCollected = $depositsCollected->plus($reservationGross);
            } else {
                $depositsCollected = $depositsCollected->plus(Money::of((string) $reservation->deposit_amount, $currency));
            }
        }

        $balanceExpectedOnArrival = $projectedRevenue
            ->minus($depositsCollected)
            ->plus($inquiryExpectedOnArrival);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total_reservations' => $reservations->count(),
            'room_nights' => $roomNights,
            'projected_room_revenue' => $projectedRevenue->getAmount()->__toString(),
            'deposits_collected' => $depositsCollected->getAmount()->__toString(),
            'balance_expected_on_arrival' => $balanceExpectedOnArrival->getAmount()->__toString(),
            'status_counts' => $statusCounts,
        ];
    }

    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     rows: Collection<int, array{
     *         reservation: Reservation,
     *         guest_name: string,
     *         room_number: string,
     *         stay_nights: int,
     *         room_revenue: string,
     *         folio_charges: string,
     *         payments_received: string,
     *         outstanding_balance: string
     *     }>,
     *     totals: array{
     *         reservations: int,
     *         room_nights: int,
     *         room_revenue: string,
     *         folio_charges: string,
     *         payments_received: string,
     *         outstanding_balance: string
     *     }
     * }
     */
    public function roomPaymentsAccounting(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();
        $paymentMode = app(\App\Domain\HBMS\Services\ReservationSettingsService::class)->all()['payment_mode'] ?? 'prepaid';

        $currency = 'TZS';
        $totalRoomRevenue = Money::of('0', $currency);
        $totalFolioCharges = Money::of('0', $currency);
        $totalPayments = Money::of('0', $currency);
        $totalOutstanding = Money::of('0', $currency);
        $totalNights = 0;

        $rows = Reservation::query()
            ->with(['guest', 'room', 'folio.payments', 'folio.transactions'])
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->orderBy('check_in_date')
            ->get()
            ->map(function (Reservation $reservation) use (
                $currency,
                $paymentMode,
                &$totalRoomRevenue,
                &$totalFolioCharges,
                &$totalPayments,
                &$totalOutstanding,
                &$totalNights
            ): array {
                $stayNights = max(
                    1,
                    Carbon::parse($reservation->check_in_date)->diffInDays(Carbon::parse($reservation->check_out_date))
                );
                $totalNights += $stayNights;

                $roomGross = Money::of((string) $reservation->daily_rate, $currency)->multipliedBy($stayNights);
                if ($reservation->status === 'cancelled' && $reservation->cancellation_charge_amount !== null) {
                    $roomGross = Money::of((string) $reservation->cancellation_charge_amount, $currency);
                }
                $roomRevenue = $reservation->status === 'inquiry'
                    ? Money::of('0', $currency)
                    : $roomGross;
                $totalRoomRevenue = $totalRoomRevenue->plus($roomRevenue);

                $folioCharges = Money::of('0', $currency);

                if ($reservation->folio !== null) {
                    foreach ($reservation->folio->transactions->whereNull('voided_at') as $tx) {
                        $txAmount = Money::of((string) $tx->amount, $currency);
                        if ($txAmount->isPositive() && $tx->transaction_type !== 'room_charge') {
                            $folioCharges = $folioCharges->plus($txAmount);
                        }
                    }
                    $settled = (string) $reservation->folio->payments->sum('amount');
                    $paymentsReceived = Money::of($settled ?: '0', $currency);
                } elseif ($paymentMode === 'prepaid' && $reservation->status !== 'inquiry') {
                    // No folio yet (confirmed but not checked-in) — prepaid assumes full upfront payment
                    $paymentsReceived = $roomGross;
                } else {
                    $paymentsReceived = Money::of((string) ($reservation->deposit_amount ?? 0), $currency);
                }

                $outstandingBalance = $roomGross->minus($paymentsReceived);

                $totalFolioCharges = $totalFolioCharges->plus($folioCharges);
                $totalPayments = $totalPayments->plus($paymentsReceived);
                $totalOutstanding = $totalOutstanding->plus($outstandingBalance);

                return [
                    'reservation' => $reservation,
                    'guest_name' => trim(($reservation->guest?->first_name ?? '').' '.($reservation->guest?->last_name ?? '')) ?: '—',
                    'room_number' => $reservation->room?->room_number ?? '—',
                    'stay_nights' => $stayNights,
                    'room_revenue' => $roomRevenue->getAmount()->__toString(),
                    'folio_charges' => $folioCharges->getAmount()->__toString(),
                    'payments_received' => $paymentsReceived->getAmount()->__toString(),
                    'outstanding_balance' => $outstandingBalance->getAmount()->__toString(),
                ];
            });

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows,
            'totals' => [
                'reservations' => $rows->count(),
                'room_nights' => $totalNights,
                'room_revenue' => $totalRoomRevenue->getAmount()->__toString(),
                'folio_charges' => $totalFolioCharges->getAmount()->__toString(),
                'payments_received' => $totalPayments->getAmount()->__toString(),
                'outstanding_balance' => $totalOutstanding->getAmount()->__toString(),
            ],
        ];
    }

    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     days: int,
     *     totals: array{
     *         rooms_available: int,
     *         room_nights_sold: int,
     *         avg_occupancy_pct: float,
     *         total_revenue: float,
     *         adr: float,
     *         revpar: float
     *     },
     *     rows: list<array{
     *         date: string,
     *         rooms_available: int,
     *         rooms_occupied: int,
     *         occupancy_pct: float,
     *         revenue: float,
     *         adr: float,
     *         revpar: float
     *     }>
     * }
     */
    public function occupancy(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to   ??= now()->endOfDay();

        $fromDate = $from->toDateString();
        $toDate   = $to->toDateString();

        $totalRooms = Room::query()->where('status', '!=', 'under_maintenance')->count();

        $reservations = Reservation::query()
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->where('check_in_date', '<=', $toDate)
            ->where('check_out_date', '>', $fromDate)
            ->get(['check_in_date', 'check_out_date', 'daily_rate']);

        $current = $from->clone()->startOfDay();
        $end     = $to->clone()->startOfDay();
        $rows    = [];

        $totalOccupied  = 0;
        $totalAvailable = 0;
        $totalRevenue   = 0.0;
        $days           = 0;

        while ($current->lte($end)) {
            $dateStr = $current->toDateString();

            $occupiedThisDay = $reservations->filter(
                fn ($r) => $r->check_in_date <= $dateStr && $r->check_out_date > $dateStr
            );

            $roomsOccupied = $occupiedThisDay->count();
            $dailyRevenue  = (float) $occupiedThisDay->sum(fn ($r) => (float) $r->daily_rate);
            $occupancyPct  = $totalRooms > 0 ? round($roomsOccupied / $totalRooms * 100, 1) : 0.0;
            $adr           = $roomsOccupied > 0 ? $dailyRevenue / $roomsOccupied : 0.0;
            $revpar        = $totalRooms > 0 ? $dailyRevenue / $totalRooms : 0.0;

            $rows[] = [
                'date'            => $dateStr,
                'rooms_available' => $totalRooms,
                'rooms_occupied'  => $roomsOccupied,
                'occupancy_pct'   => $occupancyPct,
                'revenue'         => $dailyRevenue,
                'adr'             => round($adr),
                'revpar'          => round($revpar),
            ];

            $totalOccupied  += $roomsOccupied;
            $totalAvailable += $totalRooms;
            $totalRevenue   += $dailyRevenue;
            $days++;

            $current->addDay();
        }

        $avgOccupancyPct = $totalAvailable > 0 ? round($totalOccupied / $totalAvailable * 100, 1) : 0.0;
        $avgAdr          = $totalOccupied > 0 ? round($totalRevenue / $totalOccupied) : 0.0;
        $avgRevpar        = $totalAvailable > 0 ? round($totalRevenue / $totalAvailable) : 0.0;

        return [
            'from'   => $fromDate,
            'to'     => $toDate,
            'days'   => $days,
            'totals' => [
                'rooms_available'   => $totalRooms,
                'room_nights_sold'  => $totalOccupied,
                'avg_occupancy_pct' => $avgOccupancyPct,
                'total_revenue'     => $totalRevenue,
                'adr'               => $avgAdr,
                'revpar'            => $avgRevpar,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     by_method: \Illuminate\Support\Collection,
     *     folio_total: float,
     *     direct_total: float,
     *     grand_total: float
     * }
     */
    public function paymentCollectionSummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to   ??= now()->endOfDay();

        $byMethod = Payment::query()
            ->forReporting()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'captured')
            ->selectRaw('method, COUNT(*)::integer AS count, SUM(amount) AS total')
            ->groupBy('method')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'count'  => (int) $row->count,
                'total'  => (float) $row->total,
            ]);

        $folioTotal  = (float) Payment::query()
            ->forReporting()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'captured')
            ->whereNotNull('folio_id')
            ->sum('amount');

        $directTotal = (float) Payment::query()
            ->forReporting()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'captured')
            ->whereNotNull('order_id')
            ->whereNull('folio_id')
            ->sum('amount');

        $grandTotal = (float) Payment::query()
            ->forReporting()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'captured')
            ->sum('amount');

        return [
            'from'         => $from->toDateString(),
            'to'           => $to->toDateString(),
            'by_method'    => $byMethod,
            'folio_total'  => $folioTotal,
            'direct_total' => $directTotal,
            'grand_total'  => $grandTotal,
        ];
    }

    /**
     * F&B profitability: revenue, COGS, purchases, expenses, top items, room-type performance.
     *
     * @return array{
     *     from: string,
     *     to: string,
     *     revenue: array{food: float, drinks: float, total: float},
     *     cogs: array{food: float, drinks: float, total: float},
     *     purchases: array{food: float, drinks: float, total: float},
     *     outlet_expenses: float,
     *     gross_profit: float,
     *     gross_margin_pct: float,
     *     net_contribution: float,
     *     top_items: Collection,
     *     top_room_types: Collection,
     * }
     */
    public function fbProfitability(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to   ??= now()->endOfDay();

        // --- Revenue (folio charges + direct POS cash) ---
        $folioRev = FolioTransaction::query()
            ->forReporting()
            ->whereNull('voided_at')
            ->whereBetween('posted_at', [$from, $to])
            ->whereIn('transaction_type', ['restaurant', 'bar', 'lounge'])
            ->selectRaw('transaction_type, SUM(amount) as total')
            ->groupBy('transaction_type')
            ->pluck('total', 'transaction_type');

        $directSplit = $this->orderRevenue->directPaymentRevenueSplit($from, $to);

        $foodRev   = (float) ($folioRev['restaurant'] ?? 0)
                   + (float) ($folioRev['lounge'] ?? 0)
                   + $directSplit['restaurant'];
        $drinksRev = (float) ($folioRev['bar'] ?? 0) + $directSplit['bar'];
        $totalRev  = $foodRev + $drinksRev;

        // --- Theoretical COGS (qty × menu_item.cost for closed orders) ---
        $cogsRows = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('menu_categories', 'menu_items.category_id', '=', 'menu_categories.id')
            ->join('outlets as source_outlets', 'menu_categories.outlet_id', '=', 'source_outlets.id')
            ->where('orders.status', 'closed')
            ->whereNotNull('orders.closed_at')
            ->whereBetween('orders.closed_at', [$from, $to])
            ->whereIn('source_outlets.type', ['restaurant', 'bar', 'lounge'])
            ->where('order_items.status', '!=', 'voided')
            ->selectRaw("source_outlets.type, SUM(order_items.quantity * COALESCE(menu_items.cost, 0)) as total")
            ->groupBy('source_outlets.type')
            ->pluck('total', 'source_outlets.type');

        $foodCogs   = (float) ($cogsRows['restaurant'] ?? 0);
        $drinksCogs = (float) ($cogsRows['bar'] ?? 0) + (float) ($cogsRows['lounge'] ?? 0);
        $totalCogs  = $foodCogs + $drinksCogs;

        // --- Stock purchases received in period (outlet-linked POs) ---
        $purchaseRows = PurchaseOrder::query()
            ->join('outlets', 'purchase_orders.outlet_id', '=', 'outlets.id')
            ->where('purchase_orders.status', 'received')
            ->whereBetween('purchase_orders.received_at', [$from, $to])
            ->whereIn('outlets.type', ['restaurant', 'bar', 'lounge'])
            ->selectRaw("outlets.type, SUM(purchase_orders.total_amount) as total")
            ->groupBy('outlets.type')
            ->pluck('total', 'outlets.type');

        $foodPurchases   = (float) ($purchaseRows['restaurant'] ?? 0);
        $drinksPurchases = (float) ($purchaseRows['bar'] ?? 0) + (float) ($purchaseRows['lounge'] ?? 0);
        $totalPurchases  = $foodPurchases + $drinksPurchases;

        // --- Outlet-linked expenditures for F&B outlets ---
        $outletExpenses = (float) Expenditure::query()
            ->join('outlets', 'expenditures.outlet_id', '=', 'outlets.id')
            ->whereNull('expenditures.deleted_at')
            ->whereBetween('expenditures.expense_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('outlets.type', ['restaurant', 'bar', 'lounge'])
            ->sum('expenditures.amount');

        // --- Profitability metrics ---
        $grossProfit     = $totalRev - $totalCogs;
        $grossMarginPct  = $totalRev > 0 ? round($grossProfit / $totalRev * 100, 1) : 0.0;
        $netContribution = $totalRev - $totalCogs - $totalPurchases - $outletExpenses;

        // --- Top 15 selling items by revenue ---
        $topItems = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->leftJoin('menu_categories', 'menu_items.category_id', '=', 'menu_categories.id')
            ->join('outlets as source_outlets', 'menu_categories.outlet_id', '=', 'source_outlets.id')
            ->where('orders.status', 'closed')
            ->whereNotNull('orders.closed_at')
            ->whereBetween('orders.closed_at', [$from, $to])
            ->whereIn('source_outlets.type', ['restaurant', 'bar', 'lounge'])
            ->where('order_items.status', '!=', 'voided')
            ->selectRaw("
                order_items.menu_item_id,
                menu_items.name                                              AS item_name,
                COALESCE(menu_categories.name, 'Uncategorized')             AS category,
                source_outlets.type                                          AS outlet_type,
                SUM(order_items.quantity)::integer                           AS qty_sold,
                SUM(order_items.quantity * order_items.unit_price)           AS revenue,
                SUM(order_items.quantity * COALESCE(menu_items.cost, 0))    AS cogs
            ")
            ->groupBy('order_items.menu_item_id', 'menu_items.name', 'menu_categories.name', 'source_outlets.type')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get()
            ->map(function ($row) {
                $rev  = (float) $row->revenue;
                $cost = (float) $row->cogs;
                return [
                    'name'        => $row->item_name,
                    'category'    => $row->category,
                    'outlet_type' => $row->outlet_type,
                    'qty_sold'    => (int) $row->qty_sold,
                    'revenue'     => $rev,
                    'cogs'        => $cost,
                    'profit'      => $rev - $cost,
                ];
            });

        // --- Room type performance ---
        $topRoomTypes = Reservation::query()
            ->join('room_types', 'reservations.room_type_id', '=', 'room_types.id')
            ->whereDate('reservations.check_in_date', '>=', $from)
            ->whereDate('reservations.check_in_date', '<=', $to)
            ->whereNotIn('reservations.status', ['cancelled', 'no_show'])
            ->selectRaw("
                reservations.room_type_id,
                room_types.name                                                                        AS room_type_name,
                COUNT(*)                                                                               AS reservation_count,
                SUM(GREATEST((reservations.check_out_date - reservations.check_in_date)::integer, 1)) AS room_nights,
                SUM(GREATEST((reservations.check_out_date - reservations.check_in_date)::integer, 1)
                    * reservations.daily_rate)                                                         AS revenue
            ")
            ->groupBy('reservations.room_type_id', 'room_types.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'name'         => $row->room_type_name,
                'reservations' => (int) $row->reservation_count,
                'room_nights'  => (int) $row->room_nights,
                'revenue'      => (float) $row->revenue,
            ]);

        return [
            'from'             => $from->toDateString(),
            'to'               => $to->toDateString(),
            'revenue'          => ['food' => $foodRev,      'drinks' => $drinksRev,   'total' => $totalRev],
            'cogs'             => ['food' => $foodCogs,     'drinks' => $drinksCogs,  'total' => $totalCogs],
            'purchases'        => ['food' => $foodPurchases,'drinks' => $drinksPurchases, 'total' => $totalPurchases],
            'outlet_expenses'  => $outletExpenses,
            'gross_profit'     => $grossProfit,
            'gross_margin_pct' => $grossMarginPct,
            'net_contribution' => $netContribution,
            'top_items'        => $topItems,
            'top_room_types'   => $topRoomTypes,
        ];
    }

    /**
     * Menu item sales summary grouped by division (Food/Drinks) and menu category.
     *
     * @return array{
     *     hotel_name: string,
     *     from: string,
     *     to: string,
     *     outlet_name: string,
     *     category_filter: string,
     *     categories: list<array{
     *         name: string,
     *         subcategories: list<array{
     *             name: string,
     *             items: list<array{
     *                 name: string,
     *                 quantity: int,
     *                 price_avg: float,
     *                 amount: float,
     *                 discount: float,
     *                 net_rate: float,
     *                 tax: float,
     *                 total_amount: float
     *             }>,
     *             subtotal: array{
     *                 quantity: int,
     *                 amount: float,
     *                 discount: float,
     *                 net_rate: float,
     *                 tax: float,
     *                 total_amount: float
     *             }
     *         }>,
     *         subtotal: array{
     *             quantity: int,
     *             amount: float,
     *             discount: float,
     *             net_rate: float,
     *             tax: float,
     *             total_amount: float
     *         }
     *     }>,
     *     grand_total: array{
     *         quantity: int,
     *         amount: float,
     *         discount: float,
     *         net_rate: float,
     *         tax: float,
     *         total_amount: float
     *     }
     * }
     */
    public function menuItemSalesSummary(
        Carbon $from,
        Carbon $to,
        ?string $outletId = null,
        ?string $categoryId = null,
    ): array {
        $itemsQuery = OrderItem::query()
            ->with(['menuItem.category.outlet', 'order.outlet'])
            ->where('status', '!=', 'voided')
            ->whereHas('order', function ($query) use ($from, $to, $outletId): void {
                $query->where('status', 'closed')
                    ->whereNotNull('closed_at')
                    ->whereBetween('closed_at', [$from, $to]);

                if ($outletId !== null && $outletId !== '') {
                    $query->where('outlet_id', $outletId);
                }
            });

        if ($categoryId !== null && $categoryId !== '') {
            $itemsQuery->whereHas('menuItem.category', fn ($query) => $query->where('id', $categoryId));
        }

        /** @var Collection<int, OrderItem> $items */
        $items = $itemsQuery->get();

        $orderGrossTotals = [];
        foreach ($items as $item) {
            $lineAmount = (float) $item->quantity * (float) $item->unit_price;
            $orderGrossTotals[$item->order_id] = ($orderGrossTotals[$item->order_id] ?? 0) + $lineAmount;
        }

        /** @var array<string, array<string, array<string, array<string, float|int>>>> $tree */
        $tree = [];

        foreach ($items as $item) {
            $menuItem = $item->menuItem;
            if ($menuItem === null) {
                continue;
            }

            $sourceOutlet = $menuItem->sourceOutlet();
            $topCategory  = ($sourceOutlet?->isBar() ?? false) ? 'Drinks' : 'Food';
            $subCategory  = $menuItem->category?->name ?: '-N/A-';
            $itemKey      = $menuItem->id;

            $amount   = (float) $item->quantity * (float) $item->unit_price;
            $order    = $item->order;
            $share    = ($orderGrossTotals[$item->order_id] ?? 0) > 0
                ? $amount / $orderGrossTotals[$item->order_id]
                : 0.0;
            $discount = (float) ($order?->discount_amount ?? 0) * $share;
            $tax      = (float) ($order?->tax_amount ?? 0) * $share;
            $netRate  = $amount - $discount - $tax;
            $total    = $amount - $discount;

            if (! isset($tree[$topCategory][$subCategory][$itemKey])) {
                $tree[$topCategory][$subCategory][$itemKey] = [
                    'name'         => $menuItem->name,
                    'quantity'     => 0,
                    'amount'       => 0.0,
                    'discount'     => 0.0,
                    'net_rate'     => 0.0,
                    'tax'          => 0.0,
                    'total_amount' => 0.0,
                ];
            }

            $tree[$topCategory][$subCategory][$itemKey]['quantity']     += (int) $item->quantity;
            $tree[$topCategory][$subCategory][$itemKey]['amount']       += $amount;
            $tree[$topCategory][$subCategory][$itemKey]['discount']     += $discount;
            $tree[$topCategory][$subCategory][$itemKey]['net_rate']     += $netRate;
            $tree[$topCategory][$subCategory][$itemKey]['tax']          += $tax;
            $tree[$topCategory][$subCategory][$itemKey]['total_amount'] += $total;
        }

        $categories = [];
        foreach (['Food', 'Drinks'] as $categoryName) {
            if (! isset($tree[$categoryName])) {
                continue;
            }

            $subcategories = [];
            ksort($tree[$categoryName]);

            foreach ($tree[$categoryName] as $subCategoryName => $itemRows) {
                $rows = collect($itemRows)
                    ->map(function (array $row): array {
                        $qty = (int) $row['quantity'];

                        return [
                            'name'         => $row['name'],
                            'quantity'     => $qty,
                            'price_avg'    => $qty > 0 ? round($row['amount'] / $qty, 2) : 0.0,
                            'amount'       => round($row['amount'], 2),
                            'discount'     => round($row['discount'], 2),
                            'net_rate'     => round($row['net_rate'], 2),
                            'tax'          => round($row['tax'], 2),
                            'total_amount' => round($row['total_amount'], 2),
                        ];
                    })
                    ->sortBy('name')
                    ->values()
                    ->all();

                $subcategories[] = [
                    'name'          => $subCategoryName,
                    'items'         => $rows,
                    'subtotal'      => $this->sumMenuItemSalesRows($rows),
                ];
            }

            $categorySubtotal = $this->sumMenuItemSalesRows(
                collect($subcategories)->flatMap(fn (array $sub) => $sub['items'])->all()
            );

            $categories[] = [
                'name'          => $categoryName,
                'subcategories' => $subcategories,
                'subtotal'      => $categorySubtotal,
            ];
        }

        $grandTotal = $this->sumMenuItemSalesRows(
            collect($categories)->flatMap(
                fn (array $category) => collect($category['subcategories'])->flatMap(fn (array $sub) => $sub['items'])
            )->all()
        );

        $outletName = 'All outlets';
        if ($outletId !== null && $outletId !== '') {
            $outletName = Outlet::query()->whereKey($outletId)->value('name') ?? 'Unknown outlet';
        }

        $categoryFilter = 'All categories';
        if ($categoryId !== null && $categoryId !== '') {
            $categoryFilter = MenuCategory::query()->whereKey($categoryId)->value('name') ?? 'Unknown category';
        }

        return [
            'hotel_name'      => $this->hotelName(),
            'from'            => $from->toDateString(),
            'to'              => $to->toDateString(),
            'outlet_name'     => $outletName,
            'category_filter' => $categoryFilter,
            'categories'      => $categories,
            'grand_total'     => $grandTotal,
        ];
    }

    /**
     * Room reservations summary grouped by room type and reservation status.
     *
     * @return array{
     *     hotel_name: string,
     *     from: string,
     *     to: string,
     *     room_type_filter: string,
     *     status_filter: string,
     *     categories: list<array{
     *         name: string,
     *         subcategories: list<array{
     *             name: string,
     *             items: list<array{
     *                 name: string,
     *                 room_number: string,
     *                 quantity: int,
     *                 price_avg: float,
     *                 amount: float,
     *                 discount: float,
     *                 net_rate: float,
     *                 tax: float,
     *                 total_amount: float
     *             }>,
     *             subtotal: array{
     *                 quantity: int,
     *                 amount: float,
     *                 discount: float,
     *                 net_rate: float,
     *                 tax: float,
     *                 total_amount: float
     *             }
     *         }>,
     *         subtotal: array{
     *             quantity: int,
     *             amount: float,
     *             discount: float,
     *             net_rate: float,
     *             tax: float,
     *             total_amount: float
     *         }
     *     }>,
     *     grand_total: array{
     *         quantity: int,
     *         amount: float,
     *         discount: float,
     *         net_rate: float,
     *         tax: float,
     *         total_amount: float
     *     }
     * }
     */
    public function roomReservationsSummary(
        Carbon $from,
        Carbon $to,
        ?string $roomTypeId = null,
        ?string $status = null,
    ): array {
        $paymentMode = app(\App\Domain\HBMS\Services\ReservationSettingsService::class)->all()['payment_mode'] ?? 'prepaid';

        $query = Reservation::query()
            ->with(['guest', 'room', 'roomType', 'folio.payments'])
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->orderBy('check_in_date');

        if ($roomTypeId !== null && $roomTypeId !== '') {
            $query->where('room_type_id', $roomTypeId);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        /** @var array<string, array<string, list<array<string, mixed>>>> $tree */
        $tree = [];

        foreach ($query->get() as $reservation) {
            $financials  = $this->reservationFinancials($reservation, $paymentMode);
            $roomTypeName = $reservation->roomType?->name ?? 'Unassigned room type';
            $statusName   = $this->reservationStatusLabel((string) $reservation->status);
            $guestName    = trim(($reservation->guest?->first_name ?? '').' '.($reservation->guest?->last_name ?? '')) ?: 'Guest';

            $tree[$roomTypeName][$statusName][] = [
                'name'         => sprintf('%s (%s)', $guestName, $reservation->booking_ref),
                'room_number'  => $reservation->room?->room_number ?? '—',
                'quantity'     => $financials['nights'],
                'price_avg'    => round($financials['daily_rate'], 2),
                'amount'       => round($financials['amount'], 2),
                'discount'     => round($financials['paid'], 2),
                'net_rate'     => round($financials['balance'], 2),
                'tax'          => 0.0,
                'total_amount' => round($financials['revenue'], 2),
            ];
        }

        $categories = [];
        ksort($tree);

        foreach ($tree as $roomTypeName => $statusGroups) {
            $subcategories = [];

            foreach ($this->sortedReservationStatusGroups($statusGroups) as $statusName => $items) {
                usort($items, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

                $subcategories[] = [
                    'name'     => $statusName,
                    'items'    => $items,
                    'subtotal' => $this->sumGroupedReportRows($items),
                ];
            }

            $categoryItems = collect($subcategories)->flatMap(fn (array $sub) => $sub['items'])->all();

            $categories[] = [
                'name'          => $roomTypeName,
                'subcategories' => $subcategories,
                'subtotal'      => $this->sumGroupedReportRows($categoryItems),
            ];
        }

        $grandTotal = $this->sumGroupedReportRows(
            collect($categories)->flatMap(
                fn (array $category) => collect($category['subcategories'])->flatMap(fn (array $sub) => $sub['items'])
            )->all()
        );

        $roomTypeFilter = 'All room types';
        if ($roomTypeId !== null && $roomTypeId !== '') {
            $roomTypeFilter = RoomType::query()->whereKey($roomTypeId)->value('name') ?? 'Unknown room type';
        }

        $statusFilter = 'All statuses';
        if ($status !== null && $status !== '') {
            $statusFilter = $this->reservationStatusLabel($status);
        }

        return [
            'hotel_name'       => $this->hotelName(),
            'from'             => $from->toDateString(),
            'to'               => $to->toDateString(),
            'room_type_filter' => $roomTypeFilter,
            'status_filter'    => $statusFilter,
            'categories'       => $categories,
            'grand_total'      => $grandTotal,
        ];
    }

    /** @return array{nights: int, daily_rate: float, amount: float, paid: float, balance: float, revenue: float} */
    private function reservationFinancials(Reservation $reservation, string $paymentMode): array
    {
        $currency   = config('nexstay.currency.default', 'TZS');
        $stayNights = max(
            1,
            Carbon::parse($reservation->check_in_date)->diffInDays(Carbon::parse($reservation->check_out_date))
        );
        $dailyRate = (float) $reservation->daily_rate;

        $gross = Money::of((string) $reservation->daily_rate, $currency)->multipliedBy($stayNights);
        if ($reservation->status === 'cancelled' && $reservation->cancellation_charge_amount !== null) {
            $gross = Money::of((string) $reservation->cancellation_charge_amount, $currency);
        }

        $revenue = $reservation->status === 'inquiry'
            ? Money::of('0', $currency)
            : $gross;

        if ($reservation->folio !== null) {
            $paid = Money::of((string) ($reservation->folio->payments->sum('amount') ?: '0'), $currency);
        } elseif ($paymentMode === 'prepaid' && $reservation->status !== 'inquiry') {
            $paid = $gross;
        } else {
            $paid = Money::of((string) ($reservation->deposit_amount ?? 0), $currency);
        }

        return [
            'nights'     => (int) $stayNights,
            'daily_rate' => $dailyRate,
            'amount'     => $gross->getAmount()->toFloat(),
            'paid'       => $paid->getAmount()->toFloat(),
            'balance'    => $gross->minus($paid)->getAmount()->toFloat(),
            'revenue'    => $revenue->getAmount()->toFloat(),
        ];
    }

    private function reservationStatusLabel(string $status): string
    {
        return match ($status) {
            'inquiry'     => 'Inquiry',
            'confirmed'   => 'Confirmed',
            'checked_in'  => 'Checked in',
            'checked_out' => 'Checked out',
            'cancelled'   => 'Cancelled',
            'no_show'     => 'No show',
            default       => ucwords(str_replace('_', ' ', $status)),
        };
    }

    /** @param array<string, list<array<string, mixed>>> $statusGroups */
    private function sortedReservationStatusGroups(array $statusGroups): array
    {
        $order = ['Confirmed', 'Checked in', 'Checked out', 'Inquiry', 'Cancelled', 'No show'];
        $sorted = [];

        foreach ($order as $label) {
            if (isset($statusGroups[$label])) {
                $sorted[$label] = $statusGroups[$label];
            }
        }

        foreach ($statusGroups as $label => $items) {
            if (! isset($sorted[$label])) {
                $sorted[$label] = $items;
            }
        }

        return $sorted;
    }

    /** @param list<array<string, mixed>> $rows */
    private function sumGroupedReportRows(array $rows): array
    {
        $quantity = (int) array_sum(array_column($rows, 'quantity'));
        $amount   = round(array_sum(array_column($rows, 'amount')), 2);
        $discount = round(array_sum(array_column($rows, 'discount')), 2);
        $netRate  = round(array_sum(array_column($rows, 'net_rate')), 2);
        $tax      = round(array_sum(array_column($rows, 'tax')), 2);
        $total    = round(array_sum(array_column($rows, 'total_amount')), 2);

        return [
            'quantity'     => $quantity,
            'amount'       => $amount,
            'discount'     => $discount,
            'net_rate'     => $netRate,
            'tax'          => $tax,
            'total_amount' => $total,
        ];
    }

    /** @param list<array<string, mixed>> $rows */
    private function sumMenuItemSalesRows(array $rows): array
    {
        return $this->sumGroupedReportRows($rows);
    }

    private function hotelName(): string
    {
        if (tenancy()->initialized) {
            $name = trim((string) (tenancy()->tenant->name ?? ''));

            if ($name !== '') {
                return $name;
            }
        }

        return (string) config('app.name', 'NexStay');
    }
}
