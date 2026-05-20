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
        private readonly TaxService $taxService
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
            $tax = $this->taxService->calculate($amount, $type);

            return FolioTransaction::query()->create([
                'folio_id' => $folio->id,
                'transaction_type' => $type,
                'description' => $description,
                'amount' => $amount->getAmount()->toFloat(),
                'tax_amount' => $tax->amount->getAmount()->toFloat(),
                'tax_code' => $tax->code,
                'reference_id' => $meta['reference_id'] ?? null,
                'reference_type' => $meta['reference_type'] ?? null,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);
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
