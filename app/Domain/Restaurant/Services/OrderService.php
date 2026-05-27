<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Services;

use App\Domain\HBMS\Models\Folio;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Models\OrderStatusLog;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Models\OutletTable;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\PaymentMethodSettingsService;
use App\Domain\Shared\Services\TaxService;
use App\Domain\Shared\Support\OrderNumberGenerator;
use App\Domain\Till\Models\Payment;
use App\Domain\Till\Models\TillSession;
use App\Domain\Till\Services\TillSessionService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly FolioService $folioService,
        private readonly TaxService $taxService,
        private readonly TillSessionService $tillSessionService,
        private readonly PaymentMethodSettingsService $paymentMethodSettings,
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

            $this->logOrderTransition($order, null, 'open', 'order_created');

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

                $item = OrderItem::query()->create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $menuItem->price,
                    'notes' => $line['notes'] ?? null,
                    'status' => 'pending',
                ]);

                $this->logOrderItemTransition(
                    $order,
                    $item,
                    null,
                    'pending',
                    'item_added',
                    ['menu_item_id' => $menuItem->id, 'quantity' => $line['quantity']]
                );
            }

            return $this->recalculate($order);
        });
    }

    public function fire(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            throw_if(! $order->isOpen(), \DomainException::class, 'Cannot fire a closed order.');
            $order->load('items');

            foreach ($order->items->where('status', 'pending') as $item) {
                $item->update([
                    'status' => 'sent',
                    'sent_to_kds_at' => now(),
                ]);

                $this->logOrderItemTransition($order, $item, 'pending', 'sent', 'fired_to_kitchen');
            }

            $previousStatus = $order->status;
            $order->update(['status' => 'sent_to_kitchen']);
            $this->logOrderTransition($order, $previousStatus, 'sent_to_kitchen', 'fired_to_kitchen');

            return $order->fresh(['items.menuItem']);
        });
    }

    public function postToFolio(Order $order, Folio $folio): Order
    {
        return DB::transaction(function () use ($order, $folio) {
            $this->ensureReadyForSettlement($order);
            $order = $this->recalculate($order);
            $chargeType = match ($order->outlet->type) {
                'bar' => 'bar',
                'lounge' => 'restaurant',
                default => 'restaurant',
            };

            // Charge the inclusive total — tax is already part of the price.
            $amount = Money::of((string) $order->total, config('nexstay.currency.default', 'TZS'));

            $this->folioService->postCharge(
                $folio,
                $chargeType,
                "Order {$order->order_number}",
                $amount,
                ['reference_id' => $order->id, 'reference_type' => Order::class]
            );

            $previousStatus = $order->status;
            $order->update([
                'folio_id' => $folio->id,
                'status' => 'closed',
                'closed_at' => now(),
            ]);
            $this->logOrderTransition($order, $previousStatus, 'closed', 'posted_to_folio', ['folio_id' => $folio->id]);

            if ($order->table_id) {
                OutletTable::query()->whereKey($order->table_id)->update(['status' => 'available']);
            }

            return $order->fresh();
        });
    }

    public function recordCashPayment(Order $order, Money $amount, Money $tendered, TillSession $tillSession): Payment
    {
        throw_if(
            ! $this->paymentMethodSettings->isEnabled('cash'),
            \DomainException::class,
            'Cash payments are currently disabled for this property.'
        );

        return DB::transaction(function () use ($order, $amount, $tendered, $tillSession) {
            $this->ensureReadyForSettlement($order);
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

            $previousStatus = $order->status;
            $order->update(['status' => 'closed', 'closed_at' => now()]);
            $this->logOrderTransition($order, $previousStatus, 'closed', 'cash_payment', ['payment_id' => $payment->id]);

            if ($order->table_id) {
                OutletTable::query()->whereKey($order->table_id)->update(['status' => 'available']);
            }

            return $payment;
        });
    }

    /**
     * Settle an order by direct payment (cash, mobile_money, or card) without requiring a folio.
     * If a till session is provided and the method is cash, a TillMovement is also recorded.
     */
    public function recordDirectPayment(
        Order $order,
        string $method,
        Money $amount,
        ?string $transactionRef = null,
        ?Money $tendered = null,
        ?TillSession $tillSession = null,
    ): Payment {
        throw_if(
            ! $this->paymentMethodSettings->isEnabled($method),
            \DomainException::class,
            "Payment method '{$method}' is not enabled for this property."
        );

        return DB::transaction(function () use ($order, $method, $amount, $transactionRef, $tendered, $tillSession) {
            $this->ensureReadyForSettlement($order);
            $order    = $this->recalculate($order);
            $currency = config('nexstay.currency.default', 'TZS');

            $cashChange   = null;
            $cashTendered = null;

            if ($method === 'cash' && $tendered !== null) {
                $change = $tendered->minus($amount, RoundingMode::HALF_UP);
                throw_if($change->isNegative(), \DomainException::class, 'Amount tendered is less than order total.');
                $cashTendered = $tendered->getAmount()->toFloat();
                $cashChange   = $change->getAmount()->toFloat();
            }

            $payment = Payment::query()->create([
                'order_id'        => $order->id,
                'till_session_id' => ($method === 'cash' && $tillSession !== null) ? $tillSession->id : null,
                'amount'          => $amount->getAmount()->toFloat(),
                'currency'        => $currency,
                'method'          => $method,
                'gateway_ref'     => $transactionRef,
                'cash_tendered'   => $cashTendered,
                'cash_change'     => $cashChange,
                'received_by'     => Auth::id(),
                'status'          => 'captured',
            ]);

            if ($method === 'cash' && $tillSession !== null) {
                $this->tillSessionService->recordMovement(
                    $tillSession,
                    'cash_payment',
                    $amount,
                    "Cash payment for order {$order->order_number}",
                    $payment->id,
                    Payment::class,
                );
            }

            $previousStatus = $order->status;
            $order->update(['status' => 'closed', 'closed_at' => now()]);
            $this->logOrderTransition($order, $previousStatus, 'closed', 'direct_payment', [
                'payment_id' => $payment->id,
                'method'     => $method,
            ]);

            if ($order->table_id) {
                OutletTable::query()->whereKey($order->table_id)->update(['status' => 'available']);
            }

            return $payment;
        });
    }

    public function recalculate(Order $order): Order
    {
        $order->load(['items', 'outlet']);
        $currency = config('nexstay.currency.default', 'TZS');

        // Sum item prices — these are tax-inclusive (the price IS what the guest pays)
        $total = Money::zero($currency);
        foreach ($order->items as $item) {
            if ($item->status === 'voided') {
                continue;
            }
            $line = Money::of((string) $item->unit_price, $currency)
                ->multipliedBy($item->quantity, RoundingMode::HALF_UP);
            $total = $total->plus($line);
        }

        $chargeType = match ($order->outlet?->type) {
            'bar'    => 'bar',
            'lounge' => 'restaurant',
            default  => 'restaurant',
        };

        // Extract the tax component from the inclusive total
        $tax      = $this->taxService->calculate($total, $chargeType);
        $subtotal = $total->minus($tax->amount); // net pre-tax equivalent

        $order->update([
            'subtotal'   => $subtotal->getAmount()->toFloat(), // net (for reporting)
            'tax_amount' => $tax->amount->getAmount()->toFloat(), // extracted tax portion
            'total'      => $total->getAmount()->toFloat(),    // what the guest actually pays
        ]);

        return $order->fresh(['items.menuItem']);
    }

    public function updateItemStatus(Order $order, OrderItem $item, string $toStatus, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $item, $toStatus, $reason) {
            throw_if(! $order->isOpen(), \DomainException::class, 'Cannot update item status for a closed order.');
            throw_if($item->order_id !== $order->id, \DomainException::class, 'Order item does not belong to this order.');

            $fromStatus = (string) $item->status;
            if ($fromStatus === $toStatus) {
                return $order->fresh(['items.menuItem']);
            }

            $allowedTransitions = [
                'pending' => ['sent', 'voided'],
                'sent' => ['preparing', 'voided'],
                'preparing' => ['ready', 'voided'],
                'ready' => ['served', 'voided'],
            ];

            throw_if(
                ! in_array($toStatus, $allowedTransitions[$fromStatus] ?? [], true),
                \DomainException::class,
                "Invalid status transition from {$fromStatus} to {$toStatus}."
            );

            $updates = ['status' => $toStatus];
            if ($toStatus === 'sent') {
                $updates['sent_to_kds_at'] = now();
            }
            if ($toStatus === 'preparing') {
                $updates['prepared_at'] = now();
            }
            if ($toStatus === 'served') {
                $updates['served_at'] = now();
            }

            $item->update($updates);
            $this->logOrderItemTransition($order, $item, $fromStatus, $toStatus, $reason ?? 'manual_status_update');

            $this->syncOrderStatusFromItems($order);

            return $order->fresh(['items.menuItem']);
        });
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            throw_if(
                ! $order->isOpen(),
                \DomainException::class,
                'Only open orders can be cancelled.'
            );

            $order->load('items');
            $previousStatus = (string) $order->status;

            foreach ($order->items->whereNotIn('status', ['voided']) as $item) {
                $item->update(['status' => 'voided']);
                $this->logOrderItemTransition($order, $item, (string) $item->status, 'voided', 'order_cancelled');
            }

            $order->update(['status' => 'voided', 'closed_at' => now()]);
            $this->logOrderTransition($order, $previousStatus, 'voided', 'order_cancelled');

            if ($order->table_id) {
                OutletTable::query()->whereKey($order->table_id)->update(['status' => 'available']);
            }

            return $order->fresh();
        });
    }

    private function ensureReadyForSettlement(Order $order): void
    {
        $pendingCount = $order->items()->where('status', 'pending')->count();

        throw_if(
            $pendingCount > 0,
            \DomainException::class,
            'Cannot settle an order while there are pending items. Fire or void all pending items first.'
        );
    }

    private function syncOrderStatusFromItems(Order $order): void
    {
        $order->load('items');
        $current = (string) $order->status;
        $statuses = $order->items->pluck('status')->all();

        $next = $current;
        if ($statuses === []) {
            $next = 'open';
        } elseif (count(array_unique($statuses)) === 1 && in_array($statuses[0], ['served', 'voided'], true)) {
            $next = 'served';
        } elseif (in_array('served', $statuses, true) || in_array('ready', $statuses, true) || in_array('preparing', $statuses, true)) {
            $next = 'partially_served';
        } elseif (in_array('sent', $statuses, true)) {
            $next = 'sent_to_kitchen';
        } else {
            $next = 'open';
        }

        if ($next !== $current && ! in_array($current, ['closed', 'voided'], true)) {
            $order->update(['status' => $next]);
            $this->logOrderTransition($order, $current, $next, 'auto_sync_from_items');
        }
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function logOrderTransition(Order $order, ?string $fromStatus, string $toStatus, ?string $reason = null, ?array $meta = null): void
    {
        OrderStatusLog::query()->create([
            'order_id' => $order->id,
            'entity_type' => 'order',
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'meta' => $meta,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function logOrderItemTransition(
        Order $order,
        OrderItem $item,
        ?string $fromStatus,
        string $toStatus,
        ?string $reason = null,
        ?array $meta = null
    ): void {
        OrderStatusLog::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'entity_type' => 'order_item',
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'meta' => $meta,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
        ]);
    }
}
