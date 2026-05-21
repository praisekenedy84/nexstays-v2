<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Services\ReservationSettingsService;
use App\Domain\Shared\Services\TextifySmsService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelReservation
{
    private const CANCELLABLE_STATUSES = ['inquiry', 'confirmed'];

    public function __construct(
        private readonly ReservationSettingsService $settingsService,
        private readonly TextifySmsService $smsService
    ) {}

    public function execute(Reservation $reservation): Reservation
    {
        $updatedReservation = DB::transaction(function () use ($reservation) {
            throw_if(
                ! in_array($reservation->status, self::CANCELLABLE_STATUSES, true),
                DomainException::class,
                "Cannot cancel a reservation with status: {$reservation->status}"
            );

            $settings = $this->settingsService->all();
            $currency = config('nexstay.currency.default', 'TZS');
            $policy = (string) ($settings['cancellation']['policy'] ?? 'stayed_nights');
            $refundPercentage = (float) ($settings['cancellation']['refund_percentage'] ?? 0);

            $today = now()->startOfDay();
            $checkInDate = $reservation->check_in_date?->copy()->startOfDay();
            $checkOutDate = $reservation->check_out_date?->copy()->startOfDay();
            $totalNights = (int) $reservation->total_nights;
            $stayedUntil = ($checkOutDate !== null && $today->greaterThan($checkOutDate)) ? $checkOutDate : $today;
            $stayedNights = ($checkInDate !== null && $checkOutDate !== null && $today->greaterThan($checkInDate))
                ? (int) min($totalNights, $checkInDate->diffInDays($stayedUntil))
                : 0;

            $dailyRate = Money::of((string) ($reservation->daily_rate ?? 0), $currency);
            $prepaidAmount = Money::of((string) ($reservation->deposit_amount ?? 0), $currency);

            $chargeAmount = match ($policy) {
                'stayed_nights' => $dailyRate->multipliedBy((string) $stayedNights, RoundingMode::HALF_UP),
                'percentage_refund', 'no_refund' => $prepaidAmount,
                default => $prepaidAmount,
            };

            $refundAmount = match ($policy) {
                'stayed_nights' => $prepaidAmount->minus($chargeAmount),
                'percentage_refund' => $prepaidAmount
                    ->multipliedBy((string) ($refundPercentage / 100), RoundingMode::HALF_UP),
                'no_refund' => Money::of('0', $currency),
                default => Money::of('0', $currency),
            };

            if ($refundAmount->isNegative()) {
                $refundAmount = Money::of('0', $currency);
            }

            if ($policy === 'percentage_refund') {
                $chargeAmount = $prepaidAmount->minus($refundAmount);
            }

            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_policy' => $policy,
                'cancellation_nights_used' => $stayedNights,
                'prepaid_amount_at_cancellation' => $prepaidAmount->getAmount()->__toString(),
                'cancellation_charge_amount' => $chargeAmount->getAmount()->__toString(),
                'cancellation_refund_amount' => $refundAmount->getAmount()->__toString(),
                'cancellation_refund_percentage' => $policy === 'percentage_refund' ? $refundPercentage : 0,
            ]);

            if ($reservation->room !== null) {
                $reservation->room->update(['status' => 'vacant_clean']);
            }

            return $reservation->fresh();
        });

        if ($updatedReservation instanceof Reservation) {
            $this->smsService->sendReservationCancelled($updatedReservation);
        }

        return $updatedReservation;
    }
}
