<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FolioService
{
    public function __construct(
        private readonly TaxService $taxService,
        private readonly PaymentMethodSettingsService $paymentMethodSettings,
    ) {}

    public function openFolio(Reservation $reservation): Folio
    {
        return Folio::query()->create([
            'reservation_id' => $reservation->id,
            'folio_number' => $this->generateFolioNumber(),
            'currency' => $reservation->ratePlan?->currency ?? config('nexstay.currency.default', 'TZS'),
            'status' => 'open',
        ]);
    }

    public function postCharge(
        Folio $folio,
        string $type,
        string $description,
        Money $amount,
        array $meta = []
    ): FolioTransaction {
        return DB::transaction(function () use ($folio, $type, $description, $amount, $meta) {
            // $amount is the tax-inclusive price — extract the tax portion for accounting records.
            // The full $amount is what the guest owes and is used in balance calculations.
            $tax = $this->taxService->calculate($amount, $type);

            return FolioTransaction::query()->create([
                'folio_id'         => $folio->id,
                'transaction_type' => $type,
                'description'      => $description,
                'amount'           => $amount->getAmount()->toFloat(),      // inclusive total → affects balance
                'tax_amount'       => $tax->amount->getAmount()->toFloat(), // extracted portion → for reporting only
                'tax_code'         => $tax->code,
                'reference_id'     => $meta['reference_id'] ?? null,
                'reference_type'   => $meta['reference_type'] ?? null,
                'posted_by'        => Auth::id(),
                'posted_at'        => now(),
            ]);
        });
    }

    public function postPayment(
        Folio $folio,
        Money $amount,
        string $method,
        ?string $tillSessionId = null,
        ?string $notes = null,
    ): array {
        throw_if(
            ! $this->paymentMethodSettings->isEnabled($method),
            \DomainException::class,
            "Payment method '{$method}' is not enabled for this property."
        );

        return DB::transaction(function () use ($folio, $amount, $method, $tillSessionId, $notes) {
            $transaction = FolioTransaction::query()->create([
                'folio_id'         => $folio->id,
                'transaction_type' => 'payment',
                'description'      => 'Payment — '.strtoupper($method),
                'amount'           => $amount->negated()->getAmount()->toFloat(),
                'tax_amount'       => 0,
                'tax_code'         => null,
                'posted_by'        => Auth::id(),
                'posted_at'        => now(),
            ]);

            $payment = \App\Domain\Till\Models\Payment::query()->create([
                'folio_id'        => $folio->id,
                'amount'          => $amount->getAmount()->toFloat(),
                'currency'        => $folio->currency,
                'method'          => $method,
                'till_session_id' => $tillSessionId,
                'received_by'     => Auth::id(),
                'notes'           => $notes,
                'status'          => 'captured',
            ]);

            $folio->increment('settled_amount', $amount->getAmount()->toFloat());

            return ['transaction' => $transaction, 'payment' => $payment];
        });
    }

    public function balance(Folio $folio): Money
    {
        $currency = $folio->currency;
        $sum = (string) $folio->transactions()
            ->whereNull('voided_at')
            ->sum('amount');

        return Money::of($sum, $currency);
    }

    private function generateFolioNumber(): string
    {
        $prefix = 'FLO-'.now()->format('Y').'-';

        return DB::transaction(function () use ($prefix) {
            $last = Folio::query()
                ->where('folio_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('folio_number')
                ->value('folio_number');

            $seq = $last ? ((int) substr($last, -6)) + 1 : 1;

            return $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
        });
    }
}
