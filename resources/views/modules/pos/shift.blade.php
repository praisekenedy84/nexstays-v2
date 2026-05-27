<x-layouts.app active-nav="restaurant" title="My shift summary" subtitle="Today's orders and sales">
    @php $currency = 'TZS'; @endphp

    {{-- Date picker --}}
    <form method="GET" class="mb-6 flex items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">Date</label>
            <input type="date" name="date" value="{{ $date }}" class="input-field w-auto">
        </div>
        <button type="submit" class="btn-primary">View</button>
        <a href="{{ route('tenant.restaurant.index') }}" class="btn-outline">← POS</a>
    </form>

    {{-- KPI row --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="card p-5 text-center">
            <p class="text-xs font-medium text-ink-muted">Cash collected</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $currency }} {{ number_format($summary['cash']) }}</p>
        </div>
        <div class="card p-5 text-center">
            <p class="text-xs font-medium text-ink-muted">Folio posts</p>
            <p class="mt-1 text-2xl font-bold text-indigo-700">{{ $currency }} {{ number_format($summary['folio']) }}</p>
        </div>
        <div class="card p-5 text-center">
            <p class="text-xs font-medium text-ink-muted">Total settled</p>
            <p class="mt-1 text-2xl font-bold text-ink">{{ $currency }} {{ number_format($summary['total_closed']) }}</p>
        </div>
        <div class="card p-5 text-center">
            <p class="text-xs font-medium text-ink-muted">Orders</p>
            <p class="mt-1 text-2xl font-bold text-ink">
                {{ $summary['count_closed'] }}<span class="text-base font-normal text-ink-muted"> closed</span>
                @if ($summary['count_open'] > 0)
                    <span class="text-base font-normal text-amber-600"> · {{ $summary['count_open'] }} open</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Orders table --}}
    @if ($orders->isEmpty())
        <div class="card py-12 text-center text-sm text-ink-muted">No orders on {{ $date }}.</div>
    @else
        <div class="card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-5 py-3 text-left">Order</th>
                        <th class="px-5 py-3 text-left">Outlet / Table</th>
                        <th class="px-5 py-3 text-left">Items</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-left">Method</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-left">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($orders as $order)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <a href="{{ route('tenant.pos.manage', $order) }}"
                                   class="font-mono text-xs font-semibold text-primary hover:underline">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-ink-muted">
                                {{ $order->outlet?->name ?? '—' }}
                                @if ($order->table)· T{{ $order->table->table_number }}@endif
                                @if ($order->guest_label)· {{ $order->guest_label }}@endif
                            </td>
                            <td class="px-5 py-3 text-ink-muted">
                                {{ $order->items->where('status', '!=', 'voided')->count() }} item(s)
                            </td>
                            <td class="px-5 py-3 text-right font-semibold">{{ number_format((float) $order->total) }}</td>
                            <td class="px-5 py-3">
                                @if ($order->status === 'closed')
                                    @if ($order->folio_id)
                                        <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">Folio</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Cash</span>
                                    @endif
                                @else
                                    <span class="text-ink-subtle">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 capitalize text-ink-muted">{{ str_replace('_', ' ', $order->status) }}</td>
                            <td class="px-5 py-3 text-xs text-ink-subtle">{{ $order->opened_at?->format('H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($orders->hasPages())
                <div class="border-t border-slate-100 px-5 py-3">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    @endif

</x-layouts.app>
