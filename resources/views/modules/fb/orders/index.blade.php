@php
    $currency = config('nexstay.currency.default', 'TZS');
    $statuses = [
        '' => 'All statuses',
        'open' => 'Open',
        'sent_to_kitchen' => 'Sent to kitchen',
        'partially_served' => 'Partially served',
        'served' => 'Served',
        'closed' => 'Closed',
        'voided' => 'Voided',
    ];
@endphp

<x-layouts.app active-nav="fb-orders" title="Orders & sales" subtitle="All F&B orders across outlets">
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
            <select name="waiter_id" class="input-field min-w-[160px]">
                <option value="">All staff</option>
                @foreach ($waiters as $waiter)
                    <option value="{{ $waiter->id }}" @selected($waiterId === $waiter->id)>{{ $waiter->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">Status</label>
            <select name="status" class="input-field min-w-[160px]">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[180px] flex-1">
            <label class="mb-1 block text-xs font-medium text-ink-muted">Order #</label>
            <input type="search" name="search" value="{{ $search }}" placeholder="Search…" class="input-field">
        </div>
        <button type="submit" class="btn-primary">Apply</button>
    </form>

    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">Total sales</p>
            <p class="mt-1 text-lg font-bold text-ink">{{ $currency }} {{ number_format($summary['total_closed']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">Cash</p>
            <p class="mt-1 text-lg font-bold text-emerald-700">{{ number_format($summary['cash']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">Card / M-Pesa</p>
            <p class="mt-1 text-lg font-bold text-sky-700">{{ number_format($summary['card'] + $summary['mobile_money']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">Folio</p>
            <p class="mt-1 text-lg font-bold text-indigo-700">{{ number_format($summary['folio']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">Open tabs</p>
            <p class="mt-1 text-lg font-bold text-amber-700">
                {{ $summary['count_open'] }}
                <span class="text-sm font-normal text-ink-muted">· {{ number_format($summary['open_value']) }}</span>
            </p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-ink-muted">Closed / voided</p>
            <p class="mt-1 text-lg font-bold text-ink">
                {{ $summary['count_closed'] }}
                @if ($summary['count_voided'] > 0)
                    <span class="text-sm font-normal text-ink-muted">· {{ $summary['count_voided'] }} void</span>
                @endif
            </p>
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                <tr>
                    <th class="px-5 py-3">Order</th>
                    <th class="px-5 py-3">Outlet</th>
                    <th class="px-5 py-3">Guest / table</th>
                    <th class="px-5 py-3">Staff</th>
                    <th class="px-5 py-3 text-right">Total</th>
                    <th class="px-5 py-3">Payment</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Opened</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    @php
                        $payment = $order->payments->first();
                        $paymentLabel = match (true) {
                            $order->status !== 'closed' => '—',
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
                            @if ($order->table)T{{ $order->table->table_number }}@endif
                            @if ($order->guest_label){{ $order->table ? ' · ' : '' }}{{ $order->guest_label }}@endif
                            @if (! $order->table && ! $order->guest_label)—@endif
                        </td>
                        <td class="px-5 py-3 text-xs text-ink-muted">{{ $order->waiter?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-semibold">{{ number_format((float) $order->total) }}</td>
                        <td class="px-5 py-3 text-xs">{{ $paymentLabel }}</td>
                        <td class="px-5 py-3 capitalize text-xs text-ink-muted">{{ str_replace('_', ' ', $order->status) }}</td>
                        <td class="px-5 py-3 text-xs text-ink-subtle">
                            {{ $order->opened_at?->format('d M H:i') }}
                            @if ($order->closed_at)
                                <span class="block">Closed {{ $order->closed_at->format('d M H:i') }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('tenant.fb.orders.show', $order) }}" class="text-primary hover:underline">View</a>
                            @can('manage-orders')
                                @if ($order->isOpen())
                                    <a href="{{ route('tenant.pos.manage', $order) }}" class="ml-2 text-primary hover:underline">Manage</a>
                                    <form method="POST" action="{{ route('tenant.pos.orders.cancel', $order) }}" class="ml-2 inline"
                                          onsubmit="return confirm('Cancel order {{ $order->order_number }}? All items will be voided.')">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:underline">Cancel</button>
                                    </form>
                                @elseif ($order->status === 'closed')
                                    <form method="POST" action="{{ route('tenant.pos.orders.cancel', $order) }}" class="ml-2 inline"
                                          onsubmit="return confirm('Void settled order {{ $order->order_number }}? Beverage stock will be restored and this sale will be removed from profitability reports.')">
                                        @csrf
                                        <button type="submit" class="text-amber-700 hover:underline">Void</button>
                                    </form>
                                    <form method="POST" action="{{ route('tenant.fb.orders.destroy', $order) }}" class="ml-2 inline"
                                          onsubmit="return confirm('Permanently delete order {{ $order->order_number }}? Stock will be restored and the record will be removed entirely.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @elseif ($order->status === 'voided')
                                    <form method="POST" action="{{ route('tenant.fb.orders.destroy', $order) }}" class="ml-2 inline"
                                          onsubmit="return confirm('Permanently delete order {{ $order->order_number }}? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-12 text-center text-ink-muted">No orders match your filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $orders->links() }}</div>
    </div>
</x-layouts.app>
