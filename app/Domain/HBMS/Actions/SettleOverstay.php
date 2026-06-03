<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Services\FolioService;
use Brick\Money\Money;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettleOverstay
{
    public function __construct(
        private readonly FolioService $folioService,
    ) {}

    /**
     * Settle a posted overstay charge — either collect payment or waive with a reason.
     *
     * @param  string  $settlement  'paid' records a folio payment; 'waived' writes off the overstay amount.
     */
    public function execute(
        Reservation $reservation,
        string $settlement,
        ?string $method = null,
        ?string $waiverReason = null,
        ?string $notes = null,
    ): void {
        throw_if(
            $reservation->overstay_settlement !== 'pending',
            DomainException::class,
            'There is no pending overstay charge to settle.'
        );

        $folio = $reservation->folio ?? $reservation->load('folio')->folio;

        throw_if(
            $folio === null,
            DomainException::class,
            'No folio found for this reservation.'
        );

        $chargeAmount = Money::of((string) $reservation->overstay_charge, $folio->currency);

        DB::transaction(function () use ($reservation, $folio, $settlement, $method, $waiverReason, $notes, $chargeAmount) {
            if ($settlement === 'paid') {
                throw_if(
                    $method === null || $method === '',
                    DomainException::class,
                    'A payment method is required to settle the overstay.'
                );

                $result = $this->folioService->postPayment(
                    folio: $folio,
                    amount: $chargeAmount,
                    method: $method,
                    tillSessionId: null,
                    notes: $notes ?? 'Overstay settlement',
                );

                $reservation->update([
                    'overstay_settlement'                  => 'paid',
                    'overstay_waiver_reason'               => null,
                    'overstay_notes'                       => $notes ?? $reservation->overstay_notes,
                    'overstay_settled_by'                  => Auth::id(),
                    'overstay_settled_at'                  => now(),
                    'overstay_settlement_transaction_id'   => $result['transaction']->id,
                    'overstay_settlement_payment_id'       => $result['payment']->id,
                ]);

                return;
            }

            if ($settlement === 'waived') {
                throw_if(
                    $waiverReason === null || trim($waiverReason) === '',
                    DomainException::class,
                    'A waiver reason is required when waiving an overstay charge.'
                );

                $result = $this->folioService->postWriteOff(
                    folio: $folio,
                    reason: 'Overstay waiver — '.$waiverReason,
                    amount: $chargeAmount,
                );

                $reservation->update([
                    'overstay_settlement'                  => 'waived',
                    'overstay_waiver_reason'               => $waiverReason,
                    'overstay_settled_by'                  => Auth::id(),
                    'overstay_settled_at'                  => now(),
                    'overstay_settlement_transaction_id'   => $result['transaction']->id,
                    'overstay_settlement_payment_id'       => $result['payment']->id,
                ]);

                return;
            }

            throw new DomainException("Invalid overstay settlement: {$settlement}");
        });
    }
}
