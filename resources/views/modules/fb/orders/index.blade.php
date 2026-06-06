@php
    $currency = config('nexstay.currency.default', 'TZS');
@endphp

<x-layouts.app
    active-nav="fb-orders"
    title="Sales"
    :subtitle="$canViewAll ? 'Completed F&B sales across outlets and staff' : 'Your completed F&B sales'"
>
    <form method="GET" class="mb-6 card flex flex-wrap items-end gap-3 p-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">From</label>
            <input type="date" name="from" value="{{ $from }}" class="input-field w-auto">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">To</label>
            <input type="date" name="to" value="{{ $to }}" class="input-field w-auto">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">Outlet</label>
            <select name="outlet_id" class="input-field min-w-[160px]">
                <option value="">All outlets</option>
                @foreach ($outlets as $outlet)
                    <option value="{{ $outlet->id }}" @selected($outletId === $outlet->id)>{{ $outlet->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">Staff</label>
            @if ($canViewAll ?? false)
                <select name="waiter_id" class="input-field min-w-[160px]">
                    <option value="">All staff</option>
                    @foreach ($waiters as $waiter)
                        <option value="{{ $waiter->id }}" @selected($waiterId === $waiter->id)>{{ $waiter->name }}</option>
                    @endforeach
                </select>
            @else
                <p class="input-field min-w-[160px] bg-slate-50 text-sm text-ink-muted">{{ auth()->user()->name }} (your sales only)</p>
            @endif
        </div>
        <button type="submit" class="btn-primary">Apply</button>
    </form>

    <p class="mb-4 text-sm text-ink-muted">
        Settled orders only. Manage open orders from
        <a href="{{ route('tenant.restaurant.index') }}" class="text-primary hover:underline">Restaurant</a>,
        <a href="{{ route('tenant.bar.index') }}" class="text-primary hover:underline">Bar</a>, or
        <a href="{{ route('tenant.lounge.index') }}" class="text-primary hover:underline">Lounge</a>.
    </p>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">{{ $canViewAll ? 'Total sales' : 'Your total sales' }}</p>
            <p class="mt-1 text-lg font-bold text-ink">{{ $currency }} {{ number_format($summary['total_closed']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">Cash</p>
            <p class="mt-1 text-lg font-bold text-emerald-700">{{ number_format($summary['cash']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">Card</p>
            <p class="mt-1 text-lg font-bold text-sky-700">{{ number_format($summary['card']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">Mobile money</p>
            <p class="mt-1 text-lg font-bold text-violet-700">{{ number_format($summary['mobile_money']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">Folio</p>
            <p class="mt-1 text-lg font-bold text-indigo-700">{{ number_format($summary['folio']) }}</p>
        </div>
    </div>

    <div class="mt-6 card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <h3 class="font-semibold text-ink">{{ $canViewAll ? 'Completed sales' : 'Your completed sales' }}</h3>
            <span class="text-xs text-ink-muted">{{ $summary['count_closed'] }} {{ Str::plural('order', $summary['count_closed']) }}</span>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                <tr>
                    <th class="px-5 py-3">Order</th>
                    <th class="px-5 py-3">Outlet</th>
                    <th class="px-5 py-3">Guest / table</th>
                    <th class="px-5 py-3">Staff</th>
                    <th class="px-5 py-3 text-right">Total</th>
                    <th class="px-5 py-3">Payment</th>
                    <th class="px-5 py-3">Closed</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    @php
                        $payment = $order->payments->first();
                        $paymentLabel = match (true) {
                            $order->folio_id !== null => 'Folio',
                            $payment?->method === 'cash' => 'Cash',
                            $payment?->method === 'card' => 'Card',
                            $payment?->method === 'mobile_money' => 'Mobile',
                            default => 'Paid',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-3">
                            <a href="{{ route('tenant.fb.orders.show', $order) }}" class="font-mono text-xs font-semibold text-primary hover:underline">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-ink-muted">{{ $order->outlet?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-xs text-ink-muted">
                            @if ($order->table)
                                T{{ $order->table->table_number }}
                            @endif
                            @if ($order->guest_label)
                                {{ $order->table ? ' · ' : '' }}{{ $order->guest_label }}
                            @endif
                            @if (! $order->table && ! $order->guest_label)
                                —
                            @endif
                        </td>
                        <td class="px-5 py-3 text-xs text-ink-muted">{{ $order->waiter?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-semibold">{{ number_format((float) $order->total) }}</td>
                        <td class="px-5 py-3 text-xs">{{ $paymentLabel }}</td>
                        <td class="px-5 py-3 text-xs text-ink-subtle whitespace-nowrap">
                            {{ $order->closed_at?->format('d M Y, H:i') ?? '—' }}
                        </td>
                        <td class="px-5 py-3">
                            @include('modules.pos._order_actions', ['order' => $order])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-ink-muted">No completed sales in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($orders->hasPages())
            <div class="border-t px-5 py-3">{{ $orders->links() }}</div>
        @endif
    </div>
</x-layouts.app>
