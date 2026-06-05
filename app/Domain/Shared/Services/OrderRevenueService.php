<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;

class OrderRevenueService
{
    /**
     * Split an order's line totals by folio charge type (food vs beverages).
     *
     * @return array{restaurant: Money, bar: Money}
     */
    public function splitOrderTotals(Order $order): array
    {
        $order->loadMissing(['items.menuItem.category.outlet']);
        $currency = config('nexstay.currency.default', 'TZS');
        $food = Money::zero($currency);
        $drinks = Money::zero($currency);

        foreach ($order->items as $item) {
            if ($item->status === 'voided') {
                continue;
            }

            $line = Money::of((string) $item->unit_price, $currency)
                ->multipliedBy($item->quantity, RoundingMode::HALF_UP);

            if ($item->menuItem?->isBeverage()) {
                $drinks = $drinks->plus($line);
            } else {
                $food = $food->plus($line);
            }
        }

        return ['restaurant' => $food, 'bar' => $drinks];
    }

    /**
     * Allocate direct POS payment revenue by menu item source outlet (not order outlet).
     *
     * @return array{restaurant: float, bar: float}
     */
    public function directPaymentRevenueSplit(Carbon $from, Carbon $to): array
    {
        $row = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('menu_categories', 'menu_items.category_id', '=', 'menu_categories.id')
            ->join('outlets as source_outlets', 'menu_categories.outlet_id', '=', 'source_outlets.id')
            ->whereNull('payments.folio_id')
            ->whereNotNull('payments.order_id')
            ->where('payments.status', 'captured')
            ->where('orders.status', 'closed')
            ->where('order_items.status', '!=', 'voided')
            ->whereBetween('payments.created_at', [$from, $to])
            ->selectRaw("
                SUM(CASE WHEN source_outlets.type = 'bar'
                    THEN order_items.quantity * order_items.unit_price ELSE 0 END) AS drinks,
                SUM(CASE WHEN source_outlets.type != 'bar'
                    THEN order_items.quantity * order_items.unit_price ELSE 0 END) AS food
            ")
            ->first();

        return [
            'restaurant' => (float) ($row->food ?? 0),
            'bar'        => (float) ($row->drinks ?? 0),
        ];
    }
}
