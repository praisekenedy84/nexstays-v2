<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\AncillaryService;
use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Services\FolioService;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class PostAncillaryCharge
{
    public function __construct(
        private readonly FolioService $folioService
    ) {}

    /**
     * @param  array{reservation_id: string, ancillary_service_id?: string|null, description?: string|null, amount?: float|int|string|null}  $data
     */
    public function execute(array $data): FolioTransaction
    {
        return DB::transaction(function () use ($data) {
            $reservation = Reservation::query()->with('folio')->findOrFail($data['reservation_id']);

            throw_if(
                ! in_array($reservation->status, ['checked_in', 'confirmed'], true),
                \DomainException::class,
                'Ancillary charges require an active or confirmed reservation.'
            );

            $folio = $reservation->folio;
            throw_if(! $folio, \DomainException::class, 'Reservation has no folio. Check the guest in first.');

            $service = isset($data['ancillary_service_id'])
                ? AncillaryService::query()->findOrFail($data['ancillary_service_id'])
                : null;

            $description = $data['description']
                ?? ($service ? $service->name : 'Extra service');
            $amount = Money::of(
                (string) ($data['amount'] ?? $service?->default_price ?? 0),
                $folio->currency
            );

            return $this->folioService->postCharge(
                $folio,
                $service?->charge_type ?? 'misc',
                $description,
                $amount,
                [
                    'reference_id' => $service?->id,
                    'reference_type' => $service ? AncillaryService::class : null,
                ]
            );
        });
    }
}
