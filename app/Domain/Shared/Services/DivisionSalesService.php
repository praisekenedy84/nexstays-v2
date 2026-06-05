<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Models\SalesSnapshot;
use App\Domain\Till\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DivisionSalesService
{
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

        return $this->rangedSummary(
            $date->clone()->startOfDay(),
            $date->clone()->endOfDay(),
            $date->toDateString()
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
        return $this->rangedSummary(
            now()->startOfMonth(),
            now()->endOfDay(),
            now()->format('Y-m-01').' – '.now()->toDateString()
        );
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

        // --- Direct POS payments (no folio) ---
        $directRows = Payment::query()
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->join('outlets', 'orders.outlet_id', '=', 'outlets.id')
            ->whereNull('payments.folio_id')
            ->whereNotNull('payments.order_id')
            ->where('orders.status', 'closed')
            ->whereBetween('payments.created_at', [$from, $to])
            ->where('payments.status', 'captured')
            ->selectRaw('outlets.type, SUM(payments.amount) as total')
            ->groupBy('outlets.type')
            ->pluck('total', 'outlets.type');

        // --- Room nights occupied during the from-date ---
        $dateStr    = $from->toDateString();
        $roomNights = Reservation::query()
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->where('check_in_date', '<=', $dateStr)
            ->where('check_out_date', '>', $dateStr)
            ->count();

        // --- Total payments collected in range ---
        $paymentsCollected = (float) Payment::query()
            ->forReporting()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'captured')
            ->sum('amount');

        $rooms      = (float) ($folioRows['room_charge'] ?? 0);
        $restaurant = (float) ($folioRows['restaurant'] ?? 0)
                    + (float) ($directRows['restaurant'] ?? 0);
        $bar        = (float) ($folioRows['bar'] ?? 0)
                    + (float) ($folioRows['lounge'] ?? 0)
                    + (float) ($directRows['bar'] ?? 0)
                    + (float) ($directRows['lounge'] ?? 0);
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
}
