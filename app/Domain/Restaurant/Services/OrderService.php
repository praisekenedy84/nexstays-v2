<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Services;

use App\Domain\HBMS\Models\Folio;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Models\OutletTable;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Support\OrderNumberGenerator;
use App\Domain\Till\Models\Payment;
use App\Domain\Till\Services\TillSessionService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly FolioService $folioService,
        private readonly TillSessionService $tillSessionService,
    ) {}

    /**
     * @param  list<array{menu_item_id: string, quantity: int, notes?: string|null}>  $lines
     */
    public function create(Outlet $outlet, array $data, array $lines = []): Order
    {
        return DB::transaction(function () use ($outlet, $data, $lines) {
            $order = Order::query()->create([
                'outlet_id' => $outlet->id,
                'table_id' => $data['table_id'] ?? null,
                'folio_id' => $data['folio_id'] ?? null,
                'order_number' => OrderNumberGenerator::generate(),
                'status' => 'open',
                'covers' => $data['covers'] ?? 1,
                'waiter_id' => $data['waiter_id'] ?? Auth::id(),
                'guest_label' => $data['guest_label'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($order->table_id) {
                OutletTable::query()
                    ->whereKey($order->table_id)
                    ->update(['status' => 'occupied']);
            }

            if ($lines !== []) {
                $this->addItems($order, $lines);
            }

            return $order->fresh(['items.menuItem', 'table', 'outlet']);
        });
    }

    /**
     * @param  list<array{menu_item_id: string, quantity: int, notes?: string|null}>  $lines
     */
    public function addItems(Order $order, array $lines): Order
    {
        return DB::transaction(function () use ($order, $lines) {
            throw_if(! $order->isOpen(), \DomainException::class, 'Cannot add items to a closed order.');

            foreach ($lines as $line) {
                $menuItem = MenuItem::query()->findOrFail($line['menu_item_id']);
                throw_if(! $menuItem->is_available, \DomainException::class, "Menu item {$menuItem->name} is not available.");

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $menuItem->price,
                    'notes' => $line['notes'] ?? null,
                    'status' => 'pending',
                ]);
            }

            return $this->recalculate($order);
        });
    }

    public function fire(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            throw_if(! $order->isOpen(), \DomainException::class, 'Cannot fire a closed order.');

            $order->items()->where('status', 'pending')->update([
                'status' => 'sent',
                'sent_to_kds_at' => now(),
            ]);

            $order->update(['status' => 'sent_to_kitchen']);

            return $order->fresh(['items.menuItem']);
        });
    }

    public function postToFolio(Order $order, Folio $folio): Order
    {
        return DB::transaction(function () use ($order, $folio) {
            $order = $this->recalculate($order);
            $chargeType = match ($order->outlet->type) {
                'bar' => 'bar',
                'lounge' => 'restaurant',
                default => 'restaurant',
            };

            $amount = Money::of((string) $order->total, config('nexstay.currency.default', 'TZS'));

            $this->folioService->postCharge(
                $folio,
                $chargeType,
                "Order {$order->order_number}",
                $amount,
                ['reference_id' => $order->id, 'reference_type' => Order::class]
            );

            $order->update([
                'folio_id' => $folio->id,
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            if ($order->table_id) {
                OutletTable::query()->whereKey($order->table_id)->update(['status' => 'available']);
            }

            return $order->fresh();
        });
    }

    public function recordCashPayment(Order $order, Money $amount, Money $tendered, TillSession $tillSession): Payment
    {
        return DB::transaction(function () use ($order, $amount, $tendered, $tillSession) {
            $order = $this->recalculate($order);
            $currency = config('nexstay.currency.default', 'TZS');
            $change = $tendered->minus($amount, RoundingMode::HALF_UP);

            throw_if($change->isNegative(), \DomainException::class, 'Amount tendered is less than order total.');

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'till_session_id' => $tillSession->id,
                'amount' => $amount->getAmount()->toFloat(),
                'currency' => $currency,
                'method' => 'cash',
                'cash_tendered' => $tendered->getAmount()->toFloat(),
                'cash_change' => $change->getAmount()->toFloat(),
                'received_by' => Auth::id(),
                'status' => 'captured',
            ]);

            $this->tillSessionService->recordMovement(
                $tillSession,
                'cash_payment',
                $amount,
                "Cash payment for order {$order->order_number}",
                $payment->id,
                Payment::class,
            );

            $order->update(['status' => 'closed', 'closed_at' => now()]);

            if ($order->table_id) {
                OutletTable::query()->whereKey($order->table_id)->update(['status' => 'available']);
            }

            return $payment;
        });
    }

    public function recalculate(Order $order): Order
    {
        $order->load('items');
        $currency = config('nexstay.currency.default', 'TZS');
        $subtotal = Money::zero($currency);

        $tax = Money::zero($currency);

        foreach ($order->items as $item) {
            if ($item->status === 'voided') {
                continue;
            }
            $line = Money::of((string) $item->unit_price, $currency)
                ->multipliedBy($item->quantity, RoundingMode::HALF_UP);
            $subtotal = $subtotal->plus($line);
        }

        $order->update([
            'subtotal' => $subtotal->getAmount()->toFloat(),
            'tax_amount' => $tax->getAmount()->toFloat(),
            'total' => $subtotal->plus($tax)->getAmount()->toFloat(),
        ]);

        return $order->fresh(['items.menuItem']);
    }
}
