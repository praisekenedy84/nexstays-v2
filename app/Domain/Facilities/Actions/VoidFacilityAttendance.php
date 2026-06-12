<?php

declare(strict_types=1);

namespace App\Domain\Facilities\Actions;

use App\Domain\Facilities\Models\FacilityAttendance;
use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\Till\Models\Payment;
use App\Domain\Till\Models\TillMovement;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VoidFacilityAttendance
{
    public function execute(FacilityAttendance $attendance, string $reason): FacilityAttendance
    {
        return DB::transaction(function () use ($attendance, $reason) {
            throw_if(
                $attendance->isVoided(),
                DomainException::class,
                'This attendance record has already been voided.'
            );

            if ($attendance->folio_transaction_id !== null) {
                FolioTransaction::query()
                    ->whereKey($attendance->folio_transaction_id)
                    ->whereNull('voided_at')
                    ->update([
                        'voided_at' => now(),
                        'void_reason' => $reason,
                    ]);
            }

            if ($attendance->payment_id !== null) {
                TillMovement::query()
                    ->where('reference_id', $attendance->payment_id)
                    ->where('reference_type', Payment::class)
                    ->delete();

                Payment::query()->whereKey($attendance->payment_id)->delete();
            }

            $attendance->update([
                'voided_at' => now(),
                'void_reason' => $reason,
                'voided_by' => Auth::id(),
            ]);

            return $attendance;
        });
    }
}
