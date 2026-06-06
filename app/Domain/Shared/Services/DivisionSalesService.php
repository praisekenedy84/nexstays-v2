<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\SalesSnapshot;
use App\Domain\Till\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DivisionSalesService
{
    public function __construct(
        private readonly OrderRevenueService $orderRevenue,
    ) {}

    private const FB_FOLIO_TYPES    = ['restaurant', 'bar', 'lounge'];
    private const KNOWN_FOLIO_TYPES = ['room_charge', 'restaurant', 'bar', 'lounge', 'payment'];

    /**
     * Live revenue totals for a specific day (defaults to today).
     *
     * @return array{
     *     date: string,
     *     rooms: float,
     *     restaurant: float,
     *     bar: float,
     *     ancillary: float,
     *     total: float,
     *     room_nights: int,
     *     payments_collected: float
     * }
     */
    public function liveSummary(?Carbon $date = null): array
    {
        $date ??= now();

        return $this->summaryForRange(
            $date->clone()->startOfDay(),
            $date->clone()->endOfDay()
        );
    }

    /**
     * Month-to-date revenue totals (first of this month → now).
     *
     * @return array{
     *     date: string,
     *     rooms: float,
     *     restaurant: float,
     *     bar: float,
     *     ancillary: float,
     *     total: float,
     *     room_nights: int,
     *     payments_collected: float
     * }
     */
    public function mtdSummary(): array
    {
        return $this->summaryForRange(
            now()->startOfMonth(),
            now()->endOfDay()
        );
    }

    /**
     * Posted sales summary for a date range.
     *
     * @return array{
     *     date: string,
     *     rooms: float,
     *     restaurant: float,
     *     bar: float,
     *     ancillary: float,
     *     total: float,
     *     room_nights: int,
     *     payments_collected: float
     * }
     */
    public function summaryForRange(Carbon $from, Carbon $to): array
    {
        $fromDay = $from->copy()->startOfDay();
        $toDay   = $to->copy()->startOfDay();

        $dateLabel = $fromDay->isSameDay($toDay)
            ? $fromDay->toDateString()
            : $fromDay->toDateString().' – '.$toDay->toDateString();

        return $this->rangedSummary($fromDay, $to->copy()->endOfDay(), $dateLabel);
    }

    /**
     * Sales summary report for daily, weekly, or monthly periods.
     *
     * @return array{
     *     period: string,
     *     from: string,
     *     to: string,
     *     period_label: string,
     *     summary: array{
     *         date: string,
     *         rooms: float,
     *         restaurant: float,
     *         bar: float,
     *         ancillary: float,
     *         total: float,
     *         room_nights: int,
     *         payments_collected: float
     *     },
     *     daily_rows: list<array{
     *         date: string,
     *         date_label: string,
     *         rooms: float,
     *         restaurant: float,
     *         bar: float,
     *         ancillary: float,
     *         total: float,
     *         room_nights: int,
     *         payments_collected: float
     *     }>
     * }
     */
    public function salesSummaryReport(string $period, ?Carbon $anchor = null): array
    {
        $anchor ??= now();
        $anchor = $anchor->copy()->startOfDay();

        [$from, $to, $periodLabel] = match ($period) {
            'weekly' => [
                $anchor->copy()->startOfWeek(Carbon::MONDAY),
                $anchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay(),
                'Week of '.$anchor->copy()->startOfWeek(Carbon::MONDAY)->format('d M Y'),
            ],
            'monthly' => [
                $anchor->copy()->startOfMonth(),
                $anchor->copy()->endOfMonth()->endOfDay(),
                $anchor->format('F Y'),
            ],
            default => [
                $anchor->copy()->startOfDay(),
                $anchor->copy()->endOfDay(),
                $anchor->format('d M Y'),
            ],
        };

        if ($to->isFuture()) {
            $to = now()->endOfDay();
        }

        $summary = $this->summaryForRange($from, $to);

        $dailyRows = [];
        if ($period !== 'daily') {
            $current = $from->copy()->startOfDay();
            $lastDay = $to->copy()->startOfDay();

            while ($current->lte($lastDay)) {
                $daySummary = $this->summaryForRange(
                    $current->copy()->startOfDay(),
                    $current->copy()->endOfDay()
                );

                $dailyRows[] = [
                    'date'               => $current->toDateString(),
                    'date_label'         => $current->format('D, d M Y'),
                    'rooms'              => $daySummary['rooms'],
                    'restaurant'         => $daySummary['restaurant'],
                    'bar'                => $daySummary['bar'],
                    'ancillary'          => $daySummary['ancillary'],
                    'total'              => $daySummary['total'],
                    'room_nights'        => $daySummary['room_nights'],
                    'payments_collected' => $daySummary['payments_collected'],
                ];

                $current->addDay();
            }
        }

        return [
            'period'       => $period === 'weekly' || $period === 'monthly' ? $period : 'daily',
            'from'         => $from->toDateString(),
            'to'           => $to->toDateString(),
            'period_label' => $periodLabel,
            'summary'      => $summary,
            'daily_rows'   => $dailyRows,
        ];
    }

    /**
     * Bar or lounge POS sales summary (closed orders at outlets of the given type).
     *
     * @return array{
     *     outlet_type: string,
     *     outlet_type_label: string,
     *     period: string,
     *     from: string,
     *     to: string,
     *     period_label: string,
     *     summary: array{
     *         date: string,
     *         total: float,
     *         order_count: int,
     *         folio_sales: float,
     *         direct_sales: float,
     *         payments_collected: float,
     *         cash: float,
     *         card: float,
     *         mobile_money: float
     *     },
     *     daily_rows: list<array{
     *         date: string,
     *         date_label: string,
     *         total: float,
     *         order_count: int,
     *         folio_sales: float,
     *         direct_sales: float,
     *         payments_collected: float,
     *         cash: float,
     *         card: float,
     *         mobile_money: float
     *     }>
     * }
     */
    public function outletTypeSalesSummaryReport(string $outletType, string $period, ?Carbon $anchor = null): array
    {
        if (! in_array($outletType, ['bar', 'lounge'], true)) {
            throw new \InvalidArgumentException("Unsupported outlet type: {$outletType}");
        }

        $anchor ??= now();
        $anchor = $anchor->copy()->startOfDay();

        [$from, $to, $periodLabel] = match ($period) {
            'weekly' => [
                $anchor->copy()->startOfWeek(Carbon::MONDAY),
                $anchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay(),
                'Week of '.$anchor->copy()->startOfWeek(Carbon::MONDAY)->format('d M Y'),
            ],
            'monthly' => [
                $anchor->copy()->startOfMonth(),
                $anchor->copy()->endOfMonth()->endOfDay(),
                $anchor->format('F Y'),
            ],
            default => [
                $anchor->copy()->startOfDay(),
                $anchor->copy()->endOfDay(),
                $anchor->format('d M Y'),
            ],
        };

        if ($to->isFuture()) {
            $to = now()->endOfDay();
        }

        $summary = $this->outletTypeSummaryForRange($from, $to, $outletType);

        $dailyRows = [];
        if ($period !== 'daily') {
            $current = $from->copy()->startOfDay();
            $lastDay = $to->copy()->startOfDay();

            while ($current->lte($lastDay)) {
                $daySummary = $this->outletTypeSummaryForRange(
                    $current->copy()->startOfDay(),
                    $current->copy()->endOfDay(),
                    $outletType
                );

                $dailyRows[] = array_merge(
                    [
                        'date'       => $current->toDateString(),
                        'date_label' => $current->format('D, d M Y'),
                    ],
                    $this->outletTypeDailyRowFromSummary($daySummary)
                );

                $current->addDay();
            }
        }

        return [
            'outlet_type'       => $outletType,
            'outlet_type_label' => ucfirst($outletType),
            'period'            => $period === 'weekly' || $period === 'monthly' ? $period : 'daily',
            'from'              => $from->toDateString(),
            'to'                => $to->toDateString(),
            'period_label'      => $periodLabel,
            'summary'           => $summary,
            'daily_rows'        => $dailyRows,
        ];
    }

    /**
     * Total stay value for confirmed reservations checking in within a date range.
     *
     * @return array{revenue: float, reservation_count: int, room_nights: int}
     */
    public function bookedRoomRevenue(Carbon $from, Carbon $to): array
    {
        $reservations = Reservation::query()
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->get(['check_in_date', 'check_out_date', 'daily_rate']);

        return $this->summarizeBookedReservations($reservations);
    }

    /**
     * Stay value for guests arriving today (confirmed or already checked in).
     *
     * @return array{revenue: float, reservation_count: int, room_nights: int}
     */
    public function todayArrivalBookedRevenue(): array
    {
        $today = Carbon::today();

        $reservations = Reservation::query()
            ->whereDate('check_in_date', $today)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->get(['check_in_date', 'check_out_date', 'daily_rate']);

        return $this->summarizeBookedReservations($reservations);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Reservation>  $reservations
     * @return array{revenue: float, reservation_count: int, room_nights: int}
     */
    private function summarizeBookedReservations($reservations): array
    {
        $revenue = 0.0;
        $roomNights = 0;

        foreach ($reservations as $reservation) {
            $nights = max(
                1,
                Carbon::parse($reservation->check_in_date)->diffInDays(Carbon::parse($reservation->check_out_date))
            );
            $roomNights += $nights;
            $revenue += (float) $reservation->daily_rate * $nights;
        }

        return [
            'revenue' => round($revenue, 2),
            'reservation_count' => $reservations->count(),
            'room_nights' => $roomNights,
        ];
    }

    /**
     * Last N snapshots ordered oldest-first, suitable for trend charts.
     *
     * @return Collection<int, SalesSnapshot>
     */
    public function recentSnapshots(int $days = 7): Collection
    {
        return SalesSnapshot::query()
            ->orderByDesc('snapshot_date')
            ->limit($days)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Compute and persist (upsert) a snapshot for the given date.
     */
    public function takeSnapshot(Carbon $date): SalesSnapshot
    {
        $summary = $this->liveSummary($date);

        return SalesSnapshot::query()->updateOrCreate(
            ['snapshot_date' => $date->toDateString()],
            [
                'rooms'               => $summary['rooms'],
                'restaurant'          => $summary['restaurant'],
                'bar'                 => $summary['bar'],
                'ancillary'           => $summary['ancillary'],
                'total'               => $summary['total'],
                'room_nights'         => $summary['room_nights'],
                'payments_collected'  => $summary['payments_collected'],
            ]
        );
    }

    public function refreshExistingSnapshotsForRange(Carbon $from, Carbon $to): void
    {
        $current = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($current->lte($end)) {
            if (SalesSnapshot::query()->whereDate('snapshot_date', $current)->exists()) {
                $this->takeSnapshot($current);
            }

            $current->addDay();
        }
    }

    /**
     * @return array{
     *     date: string,
     *     rooms: float,
     *     restaurant: float,
     *     bar: float,
     *     ancillary: float,
     *     total: float,
     *     room_nights: int,
     *     payments_collected: float
     * }
     */
    private function rangedSummary(Carbon $from, Carbon $to, string $dateLabel): array
    {
        // --- Folio-posted charges ---
        $folioRows = FolioTransaction::query()
            ->forReporting()
            ->whereNull('voided_at')
            ->whereBetween('posted_at', [$from, $to])
            ->where('amount', '>', 0)
            ->selectRaw('transaction_type, SUM(amount) as total')
            ->groupBy('transaction_type')
            ->pluck('total', 'transaction_type');

        // Ancillary = any folio charge not in the known structural types
        $ancillaryFolio = FolioTransaction::query()
            ->forReporting()
            ->whereNull('voided_at')
            ->whereBetween('posted_at', [$from, $to])
            ->whereNotIn('transaction_type', self::KNOWN_FOLIO_TYPES)
            ->where('amount', '>', 0)
            ->sum('amount');

        $directSplit = $this->orderRevenue->directPaymentRevenueSplit($from, $to);

        // --- Room nights occupied in range ---
        $fromDay = $from->copy()->startOfDay();
        $toDay   = $to->copy()->startOfDay();
        $roomNights = $fromDay->isSameDay($toDay)
            ? $this->occupiedRoomNightsOnDate($fromDay)
            : $this->occupiedRoomNightsInRange($fromDay, $toDay);

        // --- Total payments collected in range ---
        $paymentsCollected = (float) Payment::query()
            ->forReporting()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'captured')
            ->sum('amount');

        $rooms      = (float) ($folioRows['room_charge'] ?? 0);
        $restaurant = (float) ($folioRows['restaurant'] ?? 0)
                    + (float) ($folioRows['lounge'] ?? 0)
                    + $directSplit['restaurant'];
        $bar        = (float) ($folioRows['bar'] ?? 0)
                    + $directSplit['bar'];
        $ancillary  = (float) $ancillaryFolio;
        $total      = $rooms + $restaurant + $bar + $ancillary;

        return [
            'date'               => $dateLabel,
            'rooms'              => $rooms,
            'restaurant'         => $restaurant,
            'bar'                => $bar,
            'ancillary'          => $ancillary,
            'total'              => $total,
            'room_nights'        => $roomNights,
            'payments_collected' => $paymentsCollected,
        ];
    }

    private function occupiedRoomNightsOnDate(Carbon $date): int
    {
        $dateStr = $date->toDateString();

        return Reservation::query()
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->where('check_in_date', '<=', $dateStr)
            ->where('check_out_date', '>', $dateStr)
            ->count();
    }

    private function occupiedRoomNightsInRange(Carbon $from, Carbon $to): int
    {
        $total   = 0;
        $current = $from->copy()->startOfDay();

        while ($current->lte($to)) {
            $total += $this->occupiedRoomNightsOnDate($current);
            $current->addDay();
        }

        return $total;
    }

    /**
     * @return array{
     *     date: string,
     *     total: float,
     *     order_count: int,
     *     folio_sales: float,
     *     direct_sales: float,
     *     payments_collected: float,
     *     cash: float,
     *     card: float,
     *     mobile_money: float
     * }
     */
    private function outletTypeSummaryForRange(Carbon $from, Carbon $to, string $outletType): array
    {
        $fromDay = $from->copy()->startOfDay();
        $toDay   = $to->copy()->startOfDay();

        $dateLabel = $fromDay->isSameDay($toDay)
            ? $fromDay->toDateString()
            : $fromDay->toDateString().' – '.$toDay->toDateString();

        $ordersBase = Order::query()
            ->where('status', 'closed')
            ->whereNotNull('closed_at')
            ->whereBetween('closed_at', [$from, $to])
            ->whereHas('outlet', fn ($query) => $query->where('type', $outletType));

        $totalSales  = (float) (clone $ordersBase)->sum('total');
        $orderCount  = (int) (clone $ordersBase)->count();
        $folioSales  = (float) (clone $ordersBase)->whereNotNull('folio_id')->sum('total');
        $directSales = $totalSales - $folioSales;

        $paymentRows = Payment::query()
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->join('outlets', 'orders.outlet_id', '=', 'outlets.id')
            ->where('orders.status', 'closed')
            ->whereBetween('orders.closed_at', [$from, $to])
            ->where('outlets.type', $outletType)
            ->where('payments.status', 'captured')
            ->selectRaw("
                SUM(CASE WHEN payments.method = 'cash' THEN payments.amount ELSE 0 END) AS cash,
                SUM(CASE WHEN payments.method = 'card' THEN payments.amount ELSE 0 END) AS card,
                SUM(CASE WHEN payments.method = 'mobile_money' THEN payments.amount ELSE 0 END) AS mobile_money
            ")
            ->first();

        $cash   = (float) ($paymentRows->cash ?? 0);
        $card   = (float) ($paymentRows->card ?? 0);
        $mobile = (float) ($paymentRows->mobile_money ?? 0);

        return [
            'date'               => $dateLabel,
            'total'              => $totalSales,
            'order_count'        => $orderCount,
            'folio_sales'        => $folioSales,
            'direct_sales'       => $directSales,
            'payments_collected' => $cash + $card + $mobile,
            'cash'               => $cash,
            'card'               => $card,
            'mobile_money'       => $mobile,
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array{
     *     total: float,
     *     order_count: int,
     *     folio_sales: float,
     *     direct_sales: float,
     *     payments_collected: float,
     *     cash: float,
     *     card: float,
     *     mobile_money: float
     * }
     */
    private function outletTypeDailyRowFromSummary(array $summary): array
    {
        return [
            'total'              => $summary['total'],
            'order_count'        => $summary['order_count'],
            'folio_sales'        => $summary['folio_sales'],
            'direct_sales'       => $summary['direct_sales'],
            'payments_collected' => $summary['payments_collected'],
            'cash'               => $summary['cash'],
            'card'               => $summary['card'],
            'mobile_money'       => $summary['mobile_money'],
        ];
    }
}
