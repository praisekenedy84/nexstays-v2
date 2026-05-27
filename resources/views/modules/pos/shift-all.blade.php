<x-layouts.app active-nav="restaurant" title="All staff shift" subtitle="Daily sales by waiter">
    @php $currency = 'TZS'; @endphp

    <form method="GET" class="mb-6 flex items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">Date</label>
            <input type="date" name="date" value="{{ $date }}" class="input-field w-auto">
        </div>
        <button type="submit" class="btn-primary">View</button>
        <a href="{{ route('tenant.restaurant.index') }}" class="btn-outline">← POS</a>
    </form>

    {{-- Overall totals --}}
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="card p-5 text-center">
            <p class="text-xs font-medium text-ink-muted">Total cash</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $currency }} {{ number_format($overall['cash']) }}</p>
        </div>
        <div class="card p-5 text-center">
            <p class="text-xs font-medium text-ink-muted">Total folio posts</p>
            <p class="mt-1 text-2xl font-bold text-indigo-700">{{ $currency }} {{ number_format($overall['folio']) }}</p>
        </div>
        <div class="card p-5 text-center">
            <p class="text-xs font-medium text-ink-muted">Total settled</p>
            <p class="mt-1 text-2xl font-bold text-ink">{{ $currency }} {{ number_format($overall['total_closed']) }}</p>
        </div>
        <div class="card p-5 text-center">
            <p class="text-xs font-medium text-ink-muted">Orders closed / open</p>
            <p class="mt-1 text-2xl font-bold text-ink">
                {{ $overall['count_closed'] }}
                @if ($overall['count_open'] > 0)
                    <span class="text-base font-normal text-amber-600">· {{ $overall['count_open'] }} open</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Per-waiter sections --}}
    @forelse ($byWaiter as $waiterId => $group)
        @php $s = $group['summary']; $waiter = $group['waiter']; @endphp
        <section class="card mb-6 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-ink">{{ $waiter?->name ?? 'Unknown staff' }}</h3>
                    <p class="text-xs text-ink-muted">{{ $waiter?->email }}</p>
                </div>
                <div class="flex gap-6 text-sm">
                    <span class="text-emerald-700 font-semibold">Cash {{ $currency }} {{ number_format($s['cash']) }}</span>
                    <span class="text-indigo-700 font-semibold">Folio {{ $currency }} {{ number_format($s['folio']) }}</span>
                    <span class="font-bold text-ink">Total {{ $currency }} {{ number_format($s['total_closed']) }}</span>
                    <span class="text-ink-muted">{{ $s['count_closed'] }} closed{{ $s['count_open'] > 0 ? ' · '.$s['count_open'].' open' : '' }}</span>
                </div>
            </div>
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-5 py-2.5 text-left">Order</th>
                        <th class="px-5 py-2.5 text-left">Outlet / Table</th>
                        <th class="px-5 py-2.5 text-right">Total</th>
                        <th class="px-5 py-2.5 text-left">Method</th>
                        <th class="px-5 py-2.5 text-left">Status</th>
                        <th class="px-5 py-2.5 text-left">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($group['orders'] as $order)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-2.5">
                                <a href="{{ route('tenant.pos.manage', $order) }}"
                                   class="font-mono font-semibold text-primary hover:underline">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-5 py-2.5 text-ink-muted">
                                {{ $order->outlet?->name ?? '—' }}
                                @if ($order->table)· T{{ $order->table->table_number }}@endif
                                @if ($order->guest_label) · {{ $order->guest_label }}@endif
                            </td>
                            <td class="px-5 py-2.5 text-right font-semibold">{{ number_format((float) $order->total) }}</td>
                            <td class="px-5 py-2.5">
                                @if ($order->status === 'closed')
                                    @if ($order->folio_id)
                                        <span class="rounded-full bg-indigo-100 px-2 py-0.5 font-semibold text-indigo-700">Folio</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-semibold text-emerald-700">Cash</span>
                                    @endif
                                @else
                                    <span class="text-ink-subtle">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 capitalize text-ink-muted">{{ str_replace('_', ' ', $order->status) }}</td>
                            <td class="px-5 py-2.5 text-ink-subtle">{{ $order->opened_at?->format('H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @empty
        <div class="card py-12 text-center text-sm text-ink-muted">No orders on {{ $date }}.</div>
    @endforelse

</x-layouts.app>
