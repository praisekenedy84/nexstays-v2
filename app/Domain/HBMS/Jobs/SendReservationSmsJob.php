<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Jobs;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Services\TextifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReservationSmsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $reservationId,
        public readonly string $event,
    ) {}

    public function handle(TextifySmsService $smsService): void
    {
        $reservation = Reservation::query()->find($this->reservationId);

        if ($reservation === null) {
            return;
        }

        match ($this->event) {
            'created' => $smsService->sendReservationCreated($reservation),
            'cancelled' => $smsService->sendReservationCancelled($reservation),
            default => null,
        };
    }
}
