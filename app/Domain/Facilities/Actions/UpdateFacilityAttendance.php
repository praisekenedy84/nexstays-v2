<?php

declare(strict_types=1);

namespace App\Domain\Facilities\Actions;

use App\Domain\Facilities\Models\FacilityAttendance;
use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\Shared\Services\TaxService;
use App\Domain\Till\Models\Payment;
use Brick\Money\Money;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateFacilityAttendance
{
    public function __construct(
        private readonly TaxService $taxService,
    ) {}

    /**
     * @param  array{
     *     visitor_name?: string|null,
     *     party_size?: int|string|null,
     *     amount?: float|int|string|null,
     *     notes?: string|null,
     * }  $data
     */
    public function execute(FacilityAttendance $attendance, array $data): FacilityAttendance
    {
        return DB::transaction(function () use ($attendance, $data) {
            throw_if(
                $attendance->isVoided(),
                DomainException::class,
                'Voided records cannot be edited.'
            );

            $currency  = $attendance->currency;
            $oldAmount = Money::of((string) $attendance->amount, $currency);
            $newAmount = Money::of((string) ($data['amount'] ?? $attendance->amount), $currency);

            throw_if(
                $attendance->settlement === 'complimentary' && $newAmount->isPositive(),
                DomainException::class,
                'Complimentary visits must have zero amount.'
            );

            throw_if(
                $attendance->settlement !== 'complimentary' && ! $newAmount->isPositive(),
                DomainException::class,
                'Amount must be greater than zero unless complimentary.'
            );

            if (! $newAmount->isEqualTo($oldAmount)) {
                $this->updateLedger($attendance, $newAmount);
            }

            $visitorName = $attendance->reservation_id === null
                ? trim((string) ($data['visitor_name'] ?? $attendance->visitor_name))
                : $attendance->visitor_name;

            throw_if($visitorName === '', DomainException::class, 'Visitor name is required for walk-in guests.');

            $attendance->update([
                'visitor_name' => $visitorName,
                'party_size'   => max(1, (int) ($data['party_size'] ?? $attendance->party_size)),
                'amount'       => $newAmount->getAmount()->toFloat(),
                'notes'        => $data['notes'] ?? null,
            ]);

            return $attendance;
        });
    }

    private function updateLedger(FacilityAttendance $attendance, Money $newAmount): void
    {
        if ($attendance->folio_transaction_id !== null) {
            $tax = $this->taxService->calculate($newAmount, $attendance->facility_type);

            FolioTransaction::query()
                ->whereKey($attendance->folio_transaction_id)
                ->update([
                    'amount'     => $newAmount->getAmount()->toFloat(),
                    'tax_amount' => $tax->amount->getAmount()->toFloat(),
                    'tax_code'   => $tax->code,
                ]);

            return;
        }

        if ($attendance->payment_id !== null) {
            $payment = Payment::query()->findOrFail($attendance->payment_id);

            if ($payment->method === 'cash' && $payment->cash_tendered !== null) {
                $tendered = Money::of((string) $payment->cash_tendered, $attendance->currency);

                throw_if(
                    $tendered->isLessThan($newAmount),
                    DomainException::class,
                    'Cash tendered is less than the updated fee.'
                );

                $payment->cash_change = $tendered->minus($newAmount)->getAmount()->toFloat();
            }

            $payment->amount = $newAmount->getAmount()->toFloat();
            $payment->save();
        }
    }
}
