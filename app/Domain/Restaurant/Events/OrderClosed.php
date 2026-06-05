<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Events;

use App\Domain\Shared\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderClosed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Order $order
    ) {}
}
