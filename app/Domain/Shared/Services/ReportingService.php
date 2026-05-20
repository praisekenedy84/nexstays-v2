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
}
