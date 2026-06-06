{{--
    Shared POS hub partial — included by restaurant/bar/lounge index views.
    Expects: $outlet, $allOrders, $outletOrders, $activeTill, $outletType, $hasTables (bool)
--}}
@php
    $currency = config('nexstay.currency.default', 'TZS');
    $canCreateOrder = auth()->user()?->can('create-order');
    $statusColors = [
        'open'             => 'bg-slate-100 text-slate-700',
        'sent_to_kitchen'  => 'bg-amber-100 text-amber-800',
        'preparing'        => 'bg-orange-100 text-orange-800',
        'partially_served' => 'bg-blue-100 text-blue-800',
        'served'           => 'bg-emerald-100 text-emerald-800',
    ];

    // Aggregate stats across all active orders
    $totalRevenue   = $allOrders->sum(fn ($o) => (float) $o->total);
    $kitchenOrders  = $allOrders->whereIn('status', ['sent_to_kitchen', 'preparing', 'partially_served']);
    $kitchenCount   = $kitchenOrders->count();
    $kitchenValue   = $kitchenOrders->sum(fn ($o) => (float) $o->total);
    $servedOrders   = $allOrders->where('status', 'served');
@endphp

{{-- Top bar --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        @if ($activeTill)
            <span class="flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                Till open · float {{ $currency }} {{ number_format((float) $activeTill->float_amount) }}
            </span>
        @else
            <span class="flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600 ring-1 ring-rose-200">
                <span class="size-2 rounded-full bg-rose-500"></span>
                No till open
            </span>
            @can('manage-till')
                <a href="{{ route('tenant.till.create') }}" class="text-xs text-primary hover:underline">Open till →</a>
            @endcan
        @endif
    </div>
    <div class="flex gap-2">
        <a href="{{ route('tenant.shift.mine') }}" class="btn-outline text-xs">My shift</a>
        @can('view-fb-reports')
            <a href="{{ route('tenant.shift.all') }}" class="btn-outline text-xs">All staff shift</a>
        @endcan
    </div>
</div>

{{-- ===== AGGREGATE KPIs ===== --}}
<div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
    <div class="card px-4 py-3">
        <p class="text-xs text-ink-muted">Active orders</p>
        <p class="mt-0.5 text-xl font-bold text-ink">{{ $allOrders->count() }}</p>
    </div>
    <div class="card px-4 py-3">
        <p class="text-xs text-ink-muted">Active revenue</p>
        <p class="mt-0.5 text-xl font-bold text-indigo-700">{{ number_format($totalRevenue) }}</p>
        <p class="text-[10px] text-ink-subtle">{{ $currency }}</p>
    </div>
    <div class="card px-4 py-3">
        <p class="text-xs text-ink-muted">In kitchen</p>
        <p class="mt-0.5 text-xl font-bold text-amber-700">{{ $kitchenCount }} order{{ $kitchenCount !== 1 ? 's' : '' }}</p>
        <p class="text-[10px] text-ink-subtle">{{ $currency }} {{ number_format($kitchenValue) }} value</p>
    </div>
    <div class="card px-4 py-3">
        <p class="text-xs text-ink-muted">Ready / served</p>
        <p class="mt-0.5 text-xl font-bold text-emerald-700">{{ $servedOrders->count() }}</p>
        <p class="text-[10px] text-ink-subtle">awaiting settlement</p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-[300px_1fr]">

    {{-- Left: New order form + tables --}}
    <aside class="space-y-4">
        @can('create-order')
            <section class="card p-5">
                <h3 class="mb-4 font-semibold text-ink">New order</h3>
                <form method="POST" action="{{ route('tenant.pos.orders.create', $outlet) }}" class="space-y-3">
                    @csrf
                    @if ($hasTables)
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-muted">Table</label>
                            <select name="table_id" class="input-field w-full">
                                <option value="">— Walk-in / no table —</option>
                                @foreach ($tables ?? [] as $tbl)
                                    <option value="{{ $tbl->id }}" @disabled($tbl->status === 'occupied')>
                                        Table {{ $tbl->table_number }}
                                        @if ($tbl->status === 'occupied') (occupied) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-muted">Guest / tab label</label>
                        <input type="text" name="guest_label" placeholder="e.g. John, Table 5 group" class="input-field w-full">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-muted">Covers (pax)</label>
                        <input type="number" name="covers" value="1" min="1" max="50" class="input-field w-full">
                    </div>
                    <button type="submit" class="btn-primary w-full">Open order</button>
                </form>
            </section>
        @endcan

        @if ($hasTables && isset($tables) && $tables->isNotEmpty())
            <section class="card p-5">
                <h3 class="mb-3 font-semibold text-ink">Tables</h3>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($tables as $tbl)
                        <div @class([
                            'rounded-xl border p-2 text-center text-xs',
                            'border-primary/40 bg-primary-soft font-semibold text-primary' => $tbl->status === 'occupied',
                            'border-slate-200 bg-slate-50 text-ink-muted' => $tbl->status !== 'occupied',
                        ])>
                            <p class="text-base font-bold">{{ $tbl->table_number }}</p>
                            <p class="capitalize">{{ str_replace('_', ' ', $tbl->status) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </aside>

    {{-- Right: Orders list --}}
    <section>
        {{-- Flash --}}
        @if (session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 ring-1 ring-rose-200">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-semibold text-ink">Today's orders</h3>
            <span class="text-xs text-ink-subtle">{{ $outletOrders->count() }} total · {{ $allOrders->count() }} active</span>
        </div>

        <div class="card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Guest / table</th>
                        <th class="px-4 py-3">Staff</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Opened</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($outletOrders as $order)
                        <tr @class([
                            'hover:bg-slate-50/60',
                            'bg-primary-soft/30' => $order->waiter_id === auth()->id() && $order->isOpen(),
                        ])>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-semibold text-primary">{{ $order->order_number }}</span>
                                <span class="block text-[11px] text-ink-subtle">{{ $order->items->count() }} item(s)</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-muted">
                                @if ($order->table)
                                    T{{ $order->table->table_number }}
                                @endif
                                @if ($order->guest_label)
                                    {{ $order->table ? ' · ' : '' }}{{ $order->guest_label }}
                                @endif
                                @if (! $order->table && ! $order->guest_label)
                                    Walk-in
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-muted">{{ $order->waiter?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format((float) $order->total) }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-block rounded-full px-2 py-0.5 text-xs font-semibold capitalize',
                                    $statusColors[$order->status] ?? 'bg-slate-100 text-slate-600',
                                    'bg-slate-200 text-slate-500' => $order->status === 'closed',
                                    'bg-rose-100 text-rose-700' => $order->status === 'voided',
                                ])>{{ str_replace('_', ' ', $order->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-subtle whitespace-nowrap">
                                {{ $order->opened_at?->format('H:i') ?? '—' }}
                                @if ($order->closed_at)
                                    <span class="block">Closed {{ $order->closed_at->format('H:i') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @include('modules.pos._order_actions', ['order' => $order])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-ink-muted">No orders today. Open one using the form.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
