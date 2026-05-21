<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportingService
{
    public function __construct(
        private readonly FolioService $folioService
    ) {}

    /**
     * @return Collection<int, array{folio: Folio, balance: Money, reservation: mixed}>
     */
    public function outstandingDebts(): Collection
    {
        return Folio::query()
            ->with(['reservation.guest', 'reservation.room'])
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

        $rows = FolioTransaction::query()
            ->whereNull('voided_at')
            ->whereBetween('posted_at', [$from, $to])
            ->whereIn('transaction_type', ['restaurant', 'bar', 'lounge'])
            ->selectRaw('transaction_type, SUM(amount) as total')
            ->groupBy('transaction_type')
            ->pluck('total', 'transaction_type');

        $food = (float) ($rows['restaurant'] ?? 0);
        $drinks = (float) (($rows['bar'] ?? 0) + ($rows['lounge'] ?? 0));

        return [
            'food' => (string) $food,
            'drinks' => (string) $drinks,
            'total' => (string) ($food + $drinks),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
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
            ->with(['guest', 'room', 'folio.transactions'])
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
                $folioPayments = Money::of('0', $currency);
                $folioSettled = Money::of((string) ($reservation->folio?->settled_amount ?? 0), $currency);

                if ($reservation->folio !== null) {
                    foreach ($reservation->folio->transactions->whereNull('voided_at') as $transaction) {
                        $amount = Money::of((string) $transaction->amount, $currency);

                        if ($amount->isNegative()) {
                            $folioPayments = $folioPayments->plus($amount->abs());
                        } else {
                            $folioCharges = $folioCharges->plus($amount);
                        }
                    }
                }

                $paymentsReceived = $paymentMode === 'prepaid'
                    ? ($reservation->status === 'inquiry' ? Money::of('0', $currency) : $roomGross)
                    : $folioPayments
                        ->plus($folioSettled)
                        ->plus(Money::of((string) ($reservation->deposit_amount ?? 0), $currency));

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
}
