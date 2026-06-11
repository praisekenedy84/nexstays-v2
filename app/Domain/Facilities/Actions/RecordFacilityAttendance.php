<?php

declare(strict_types=1);

namespace App\Domain\Facilities\Actions;

use App\Domain\Facilities\Models\FacilityAttendance;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\PaymentMethodSettingsService;
use App\Domain\Till\Models\Payment;
use App\Domain\Till\Models\TillSession;
use App\Domain\Till\Services\TillSessionService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecordFacilityAttendance
{
    public function __construct(
        private readonly FolioService $folioService,
        private readonly TillSessionService $tillSessionService,
        private readonly PaymentMethodSettingsService $paymentMethodSettings,
    ) {}

    /**
     * @param  array{
     *     facility_type: string,
     *     visitor_name?: string|null,
     *     reservation_id?: string|null,
     *     amount?: float|int|string|null,
     *     settlement: string,
     *     till_session_id?: string|null,
     *     cash_tendered?: float|int|string|null,
     *     transaction_ref?: string|null,
     *     notes?: string|null,
     * }  $data
     */
    public function execute(array $data): FacilityAttendance
    {
        return DB::transaction(function () use ($data) {
            $facilityType = $data['facility_type'];
            $settlement   = $data['settlement'];
            $currency     = config('nexstay.currency.default', 'TZS');
            $amount       = Money::of((string) ($data['amount'] ?? 0), $currency);

            $reservation = isset($data['reservation_id'])
                ? Reservation::query()->with(['guest', 'folio'])->findOrFail($data['reservation_id'])
                : null;

            $visitorName = $this->resolveVisitorName($data, $reservation);
            $guestId     = $reservation?->guest_id;

            throw_if(
                $settlement === 'folio' && ! $reservation,
                \DomainException::class,
                'Folio settlement requires a hotel guest reservation.'
            );

            throw_if(
                $settlement === 'folio' && ! in_array($reservation?->status, ['checked_in', 'confirmed'], true),
                \DomainException::class,
                'Folio charges require an active or confirmed reservation.'
            );

            throw_if(
                $settlement === 'folio' && ! $reservation?->folio,
                \DomainException::class,
                'Reservation has no folio. Check the guest in first.'
            );

            throw_if(
                $settlement !== 'complimentary' && $amount->isZero(),
                \DomainException::class,
                'Amount must be greater than zero unless complimentary.'
            );

            throw_if(
                $settlement === 'complimentary' && $amount->isPositive(),
                \DomainException::class,
                'Complimentary visits must have zero amount.'
            );

            $paymentId          = null;
            $folioTransactionId = null;
            $tillSessionId      = null;

            if ($settlement === 'folio') {
                $folio = $reservation->folio;
                $transaction = $this->folioService->postCharge(
                    $folio,
                    $facilityType,
                    "{$this->facilityLabel($facilityType)} — {$visitorName}",
                    $amount,
                    [
                        'reference_type' => FacilityAttendance::class,
                    ]
                );
                $folioTransactionId = $transaction->id;
            } elseif ($settlement !== 'complimentary') {
                throw_if(
                    ! $this->paymentMethodSettings->isEnabled($settlement),
                    \DomainException::class,
                    "Payment method '{$settlement}' is not enabled for this property."
                );

                if ($settlement === 'cash') {
                    $tillSession = ! empty($data['till_session_id'])
                        ? TillSession::query()->find($data['till_session_id'])
                        : null;
                    $tillSessionId = $tillSession?->id;

                    $tendered = Money::of((string) ($data['cash_tendered'] ?? $amount->getAmount()), $currency);
                    $change   = $tendered->minus($amount, RoundingMode::HALF_UP);
                    throw_if($change->isNegative(), \DomainException::class, 'Amount tendered is less than fee.');

                    $payment = Payment::query()->create([
                        'till_session_id' => $tillSessionId,
                        'amount'          => $amount->getAmount()->toFloat(),
                        'currency'        => $currency,
                        'method'          => 'cash',
                        'cash_tendered'   => $tendered->getAmount()->toFloat(),
                        'cash_change'     => $change->getAmount()->toFloat(),
                        'received_by'     => Auth::id(),
                        'notes'           => $data['notes'] ?? null,
                        'status'          => 'captured',
                    ]);

                    if ($tillSession !== null) {
                        $this->tillSessionService->recordMovement(
                            $tillSession,
                            'cash_payment',
                            $amount,
                            "{$this->facilityLabel($facilityType)} — {$visitorName}",
                            $payment->id,
                            Payment::class,
                        );
                    }
                } else {
                    $payment = Payment::query()->create([
                        'amount'      => $amount->getAmount()->toFloat(),
                        'currency'    => $currency,
                        'method'      => $settlement,
                        'gateway_ref' => $data['transaction_ref'] ?? null,
                        'received_by' => Auth::id(),
                        'notes'       => $data['notes'] ?? null,
                        'status'      => 'captured',
                    ]);
                }

                $paymentId = $payment->id;
            }

            return FacilityAttendance::query()->create([
                'facility_type'        => $facilityType,
                'visitor_name'         => $visitorName,
                'guest_id'             => $guestId,
                'reservation_id'       => $reservation?->id,
                'amount'               => $amount->getAmount()->toFloat(),
                'currency'             => $currency,
                'settlement'           => $settlement,
                'payment_id'           => $paymentId,
                'folio_transaction_id' => $folioTransactionId,
                'till_session_id'      => $tillSessionId,
                'notes'                => $data['notes'] ?? null,
                'recorded_by'          => Auth::id(),
                'attended_at'          => now(),
            ]);
        });
    }

    /** @param  array<string, mixed>  $data */
    private function resolveVisitorName(array $data, ?Reservation $reservation): string
    {
        if ($reservation?->guest) {
            return trim($reservation->guest->first_name.' '.$reservation->guest->last_name);
        }

        $name = trim((string) ($data['visitor_name'] ?? ''));

        throw_if($name === '', \DomainException::class, 'Visitor name is required for walk-in guests.');

        return $name;
    }

    private function facilityLabel(string $type): string
    {
        return match ($type) {
            'pool' => 'Swimming Pool',
            'gym' => 'Gym',
            default => ucfirst($type),
        };
    }
}
