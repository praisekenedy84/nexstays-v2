<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Actions;

use App\Domain\Bar\Models\BarTab;
use App\Domain\Restaurant\Services\OrderSettlementReversalService;
use App\Domain\Shared\Models\Order;
use Illuminate\Support\Facades\DB;

class DeleteOrder
{
    public function __construct(
        private readonly OrderSettlementReversalService $settlementReversal
    ) {}

    public function execute(Order $order): void
    {
        throw_if(
            $order->isOpen(),
            \DomainException::class,
            'Cancel the order before permanently deleting it.'
        );

        DB::transaction(function () use ($order) {
            if ($order->status === 'closed') {
                $this->settlementReversal->execute($order, 'Order permanently deleted');
            }

            BarTab::query()
                ->where('order_id', $order->id)
                ->update(['order_id' => null]);

            $order->delete();
        });
    }
}
