<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Services\DivisionSalesService;
use App\Domain\Till\Models\Payment;
use App\Domain\Till\Models\TillMovement;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeleteReservationPermanently
{
    /** @var list<string> */
    private const FORCE_ONLY_STATUSES = ['checked_in', 'checked_out'];

    public function execute(Reservation $reservation): void
    {
        $canForce = Auth::user()?->can('force-delete-reservations') ?? false;
        $checkInDate = $reservation->check_in_date?->copy();
        $checkOutDate = $reservation->check_out_date?->copy();

        DB::transaction(function () use ($reservation, $canForce) {
            $reservation->loadMissing(['room', 'folio.transactions', 'folio.payments']);

            if (in_array($reservation->status, self::FORCE_ONLY_STATUSES, true)) {
                throw_if(
                    ! $canForce,
                    DomainException::class,
                    'Checked-in or checked-out reservations require force-delete permission.'
                );
            }

            $folio = $reservation->folio;

            if ($folio !== null) {
                $hasTransactions = $folio->transactions()->exists();

                throw_if(
                    $hasTransactions && ! $canForce,
                    DomainException::class,
                    'Reservation has folio transactions and cannot be permanently deleted.'
                );

                if ($hasTransactions) {
                    $this->purgeFolioData($folio, $reservation);
                }

                $folio->forceDelete();
            }

            if (
                $reservation->room !== null
                && in_array($reservation->room->status, ['blocked', 'occupied'], true)
            ) {
                $reservation->room->update(['status' => 'vacant_clean']);
            }

            $reservation->forceDelete();
        });

        if ($checkInDate !== null) {
            $snapshotEnd = now()->startOfDay();
            if ($checkOutDate !== null && $checkOutDate->copy()->startOfDay()->lt($snapshotEnd)) {
                $snapshotEnd = $checkOutDate->copy()->startOfDay();
            }

            app(DivisionSalesService::class)->refreshExistingSnapshotsForRange(
                $checkInDate,
                $snapshotEnd,
            );
        }
    }

    private function purgeFolioData(Folio $folio, Reservation $reservation): void
    {
        $reservation->update([
            'overstay_charge_transaction_id' => null,
            'overstay_settlement_transaction_id' => null,
            'overstay_settlement_payment_id' => null,
        ]);

        $paymentIds = $folio->payments()->pluck('id');

        if ($paymentIds->isNotEmpty()) {
            TillMovement::query()
                ->where('reference_type', Payment::class)
                ->whereIn('reference_id', $paymentIds)
                ->delete();

            Payment::query()->whereIn('id', $paymentIds)->delete();
        }

        $folio->transactions()->delete();
    }
}
