<?php

declare(strict_types=1);

namespace App\Domain\Till\Services;

use App\Domain\Till\Models\TillMovement;
use App\Domain\Till\Models\TillSession;
use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TillSessionService
{
    public function open(?string $outletId, Money $float, string $currency): TillSession
    {
        return DB::transaction(function () use ($outletId, $float, $currency) {
            $existing = TillSession::query()
                ->where('outlet_id', $outletId)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            throw_if($existing !== null, \DomainException::class, 'An active till session already exists for this outlet.');

            $session = TillSession::query()->create([
                'outlet_id' => $outletId,
                'opened_by' => Auth::id(),
                'float_amount' => $float->getAmount()->toFloat(),
                'currency' => $currency,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            TillMovement::query()->create([
                'till_session_id' => $session->id,
                'movement_type' => 'opening_float',
                'amount' => $float->getAmount()->toFloat(),
                'currency' => $currency,
                'description' => 'Opening float',
                'performed_by' => Auth::id(),
            ]);

            return $session;
        });
    }

    public function activeForOutlet(?string $outletId, bool $withMovements = false): ?TillSession
    {
        $query = TillSession::query()
            ->where('outlet_id', $outletId)
            ->where('status', 'open');

        if ($withMovements) {
            $query->with('movements');
        }

        return $query->first();
    }

    public function systemCash(TillSession $session): Money
    {
        $sum = (string) $session->movements()->sum('amount');

        return Money::of($sum, $session->currency);
    }

    public function close(TillSession $session, Money $declaredCash, ?string $managerNotes = null): TillSession
    {
        return DB::transaction(function () use ($session, $declaredCash, $managerNotes) {
            throw_if(! $session->isOpen(), \DomainException::class, 'Till session is not open.');

            $system = $this->systemCash($session);
            $overShort = $declaredCash->minus($system);

            $session->update([
                'status' => 'closed',
                'closed_by' => Auth::id(),
                'closed_at' => now(),
                'declared_cash' => $declaredCash->getAmount()->toFloat(),
                'system_cash' => $system->getAmount()->toFloat(),
                'over_short' => $overShort->getAmount()->toFloat(),
                'manager_notes' => $managerNotes,
            ]);

            return $session->fresh();
        });
    }

    public function recordMovement(
        TillSession $session,
        string $type,
        Money $amount,
        string $description,
        ?string $referenceId = null,
        ?string $referenceType = null,
    ): TillMovement {
        throw_if(! $session->isOpen(), \DomainException::class, 'Till session is not open.');

        return TillMovement::query()->create([
            'till_session_id' => $session->id,
            'movement_type' => $type,
            'amount' => $amount->getAmount()->toFloat(),
            'currency' => $session->currency,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'description' => $description,
            'performed_by' => Auth::id(),
        ]);
    }
}
