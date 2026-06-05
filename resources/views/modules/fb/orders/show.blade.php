@php
    $currency = config('nexstay.currency.default', 'TZS');
    $activeItems = $order->items->where('status', '!=', 'voided');
    $payment = $order->payments->first();
    $outletNav = match($order->outlet?->type) {
        'bar'    => 'bar',
        'lounge' => 'lounge',
        default  => 'restaurant',
    };
    $backRoute = match($order->outlet?->type) {
        'bar'    => route('tenant.bar.index'),
        'lounge' => route('tenant.lounge.index'),
        default  => route('tenant.restaurant.index'),
    };
@endphp

<x-layouts.app :active-nav="$outletNav" :title="$order->order_number" subtitle="Order detail">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ $backRoute }}" class="text-sm text-primary hover:underline">← Back to {{ $order->outlet?->name ?? 'outlet' }}</a>
        <div class="flex flex-wrap gap-2">
            @if ($order->status === 'closed')
                <a href="{{ route('tenant.pos.orders.receipt', $order) }}" target="_blank" class="btn-outline text-sm">Receipt</a>
            @endif
            @if ($canManage && $order->isOpen())
                <a href="{{ route('tenant.pos.manage', $order) }}" class="btn-primary text-sm">Manage</a>
            @endif
        </div>
    </div>

    <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-mono text-lg font-bold text-primary">{{ $order->order_number }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $order->outlet?->name }}</p>
                </div>
                <x-ui.status-badge :status="$order->status" />
            </div>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-ink-muted">Table / guest</dt>
                    <dd>
                        @if ($order->table)Table {{ $order->table->table_number }}@endif
                        @if ($order->guest_label){{ $order->table ? ' · ' : '' }}{{ $order->guest_label }}@endif
                        @if (! $order->table && ! $order->guest_label)—@endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">Covers</dt>
                    <dd>{{ $order->covers }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">Waiter</dt>
                    <dd>{{ $order->waiter?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">Opened</dt>
                    <dd>{{ $order->opened_at?->format('d M Y H:i') ?? '—' }}</dd>
                </div>
                @if ($order->closed_at)
                    <div>
                        <dt class="text-xs text-ink-muted">Closed</dt>
                        <dd>{{ $order->closed_at->format('d M Y H:i') }}</dd>
                    </div>
                @endif
                @if ($order->folio)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-ink-muted">Folio</dt>
                        <dd>
                            @php $guest = $order->folio->reservation?->guest; @endphp
                            {{ trim(($guest?->first_name ?? '').' '.($guest?->last_name ?? '')) ?: 'Guest folio' }}
                            @if ($order->folio->reservation?->room)
                                · Room {{ $order->folio->reservation->room->room_number }}
                            @endif
                        </dd>
                    </div>
                @endif
                @if ($order->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-ink-muted">Notes</dt>
                        <dd>{{ $order->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink">Totals</h3>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Subtotal</dt><dd>@money($order->subtotal)</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Tax</dt><dd>@money($order->tax_amount)</dd></div>
                <div class="flex justify-between border-t pt-2 text-base font-bold">
                    <dt>Total</dt><dd>@money($order->total)</dd>
                </div>
            </dl>
            @if ($order->status === 'closed')
                <div class="mt-4 border-t pt-3 text-sm">
                    <p class="text-xs font-medium text-ink-muted">Settlement</p>
                    @if ($order->folio_id)
                        <p class="mt-1 text-indigo-700 font-semibold">Posted to folio</p>
                    @elseif ($payment)
                        <p class="mt-1 capitalize font-semibold">{{ str_replace('_', ' ', $payment->method) }}</p>
                        <p class="text-xs text-ink-muted">@money($payment->amount) · {{ $payment->receiver?->name ?? '—' }}</p>
                    @else
                        <p class="mt-1 text-ink-muted">Closed</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-6 overflow-hidden">
        <div class="border-b px-5 py-3">
            <h3 class="font-semibold text-ink">Line items</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-2 text-left">Item</th>
                    <th class="px-5 py-2 text-right">Qty</th>
                    <th class="px-5 py-2 text-right">Unit</th>
                    <th class="px-5 py-2 text-right">Line</th>
                    <th class="px-5 py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($order->items as $item)
                    <tr @class(['opacity-50' => $item->status === 'voided'])>
                        <td class="px-5 py-3">{{ $item->menuItem?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right">{{ $item->quantity }}</td>
                        <td class="px-5 py-3 text-right">@money($item->unit_price)</td>
                        <td class="px-5 py-3 text-right font-medium">@money((float) $item->unit_price * $item->quantity)</td>
                        <td class="px-5 py-3 capitalize text-xs text-ink-muted">{{ str_replace('_', ' ', $item->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t bg-slate-50 text-sm">
                <tr>
                    <td colspan="3" class="px-5 py-3 text-right font-medium text-ink-muted">Active items</td>
                    <td class="px-5 py-3 text-right font-bold">{{ $activeItems->count() }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($canManage)
        <div class="card mb-6 p-5">
            <h3 class="mb-3 text-sm font-semibold text-ink">Actions</h3>
            @include('modules.pos._order_actions', ['order' => $order])
        </div>
    @endif

    @if ($order->statusLogs->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="border-b px-5 py-3">
                <h3 class="font-semibold text-ink">Activity</h3>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($order->statusLogs as $log)
                    <li class="px-5 py-3 flex flex-wrap justify-between gap-2">
                        <span class="text-ink-muted">
                            {{ $log->entity_type === 'order_item' ? 'Item' : 'Order' }}:
                            {{ $log->from_status ? $log->from_status.' → ' : '' }}{{ $log->to_status }}
                            @if ($log->reason)<span class="text-ink-subtle">({{ $log->reason }})</span>@endif
                        </span>
                        <span class="text-xs text-ink-subtle">{{ $log->changed_at?->format('d M H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-layouts.app>
