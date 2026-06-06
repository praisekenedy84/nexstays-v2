<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Actions;

use App\Domain\Bar\Models\BarTab;
use App\Domain\Shared\Models\Order;
use Illuminate\Support\Facades\DB;

class DeleteOrder
{
    public function execute(Order $order): void
    {
        throw_if(
            $order->status === 'closed',
            \DomainException::class,
            'Void the order before permanently deleting it.'
        );

        throw_if(
            $order->isOpen(),
            \DomainException::class,
            'Cancel the order before permanently deleting it.'
        );

        throw_if(
            $order->status !== 'voided',
            \DomainException::class,
            'Only voided orders can be permanently deleted.'
        );

        DB::transaction(function () use ($order) {
            BarTab::query()
                ->where('order_id', $order->id)
                ->update(['order_id' => null]);

            $order->delete();
        });
    }
}
