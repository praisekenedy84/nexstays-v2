<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Events;

use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuestCheckedIn
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public Room $room,
        public Folio $folio
    ) {}
}
