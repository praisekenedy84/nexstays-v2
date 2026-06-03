<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Services\FolioService;
use Brick\Money\Money;
use DomainException;
use Illuminate\Support\Facades\DB;

class PostOverstayCharge
{
    public function __construct(
        private readonly FolioService $folioService,
    ) {}

    /**
     * Post the overstay room charge to the guest folio. Settlement (pay or waive) is a separate step.
     *
     * @param  float|null  $rateOverride  Nightly rate to use instead of reservation daily_rate.
     */
    public function execute(
        Reservation $reservation,
        ?string $notes = null,
        ?float $rateOverride = null,
    ): FolioTransaction {
        throw_if(
            $reservation->status !== 'checked_in',
            DomainException::class,
            'Overstay can only be recorded for a checked-in reservation.'
        );

        throw_if(
            $reservation->overstay_settlement !== null,
            DomainException::class,
            'Overstay has already been recorded for this stay.'
        );

        $preview = $reservation->overstayPreview($rateOverride);

        throw_if(
            $preview === null,
            DomainException::class,
            'No overstay detected — guest has not exceeded their check-out date.'
        );

        $nights      = $preview['nights'];
        $dailyRate   = $preview['rate'];
        $totalCharge = $preview['charge'];
        $nightLabel  = $nights === 1 ? 'night' : 'nights';

        return DB::transaction(function () use ($reservation, $notes, $nights, $dailyRate, $totalCharge, $nightLabel) {
            $folio = $reservation->folio ?? $reservation->load('folio')->folio;

            throw_if(
                $folio === null,
                DomainException::class,
                'No folio found — guest must be checked in before posting overstay charges.'
            );

            $transaction = $this->folioService->postCharge(
                folio: $folio,
                type: 'room_charge',
                description: "Overstay — {$nights} extra {$nightLabel}",
                amount: Money::of((string) $totalCharge, $folio->currency),
                meta: [
                    'reference_id' => $reservation->id,
                    'reference_type' => Reservation::class,
                ],
            );

            $reservation->update([
                'overstay_nights'                  => $nights,
                'overstay_rate'                    => $dailyRate,
                'overstay_charge'                  => $totalCharge,
                'overstay_settlement'              => 'pending',
                'overstay_notes'                   => $notes,
                'overstay_waiver_reason'           => null,
                'overstay_settled_by'              => null,
                'overstay_settled_at'              => null,
                'overstay_charge_transaction_id'  => $transaction->id,
            ]);

            return $transaction;
        });
    }
}
