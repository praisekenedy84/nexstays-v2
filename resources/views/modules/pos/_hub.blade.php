{{--
    Shared POS hub partial — included by restaurant/bar/lounge index views.
    Expects: $outlet, $myOrders, $allOrders, $activeTill, $outletType, $hasTables (bool)
--}}
@php
    $currency = config('nexstay.currency.default', 'TZS');
    $canManage = auth()->user()?->can('manage-orders');
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
        @can('manage-orders')
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

        {{-- My orders --}}
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-semibold text-ink">My open orders</h3>
            <span class="text-xs text-ink-subtle">{{ $myOrders->count() }} active</span>
        </div>

        @forelse ($myOrders as $order)
            <article class="card mb-3 p-4 transition hover:ring-2 hover:ring-primary/20">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="font-mono text-sm font-semibold text-primary">{{ $order->order_number }}</p>
                        <p class="text-xs text-ink-muted">
                            {{ $order->table ? 'Table '.$order->table->table_number.' ·' : '' }}
                            {{ $order->guest_label ?? 'Walk-in' }} ·
                            {{ $order->items->count() }} item(s)
                        </p>
                        <p class="mt-0.5 text-[11px] text-ink-subtle">
                            Opened {{ $order->opened_at?->format('H:i') ?? '—' }}
                            · {{ $order->opened_at?->diffForHumans() ?? '' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                            $statusColors[$order->status] ?? 'bg-slate-100 text-slate-600',
                        ])>{{ str_replace('_', ' ', $order->status) }}</span>
                        <p class="font-bold text-ink">{{ $currency }} {{ number_format((float) $order->total) }}</p>
                        <a href="{{ route('tenant.pos.manage', $order) }}" class="btn-primary text-xs">Manage →</a>
                    </div>
                </div>
            </article>
        @empty
            <p class="mb-6 text-sm text-ink-muted">You have no active orders. Use the form to open one.</p>
        @endforelse

        {{-- Other staff orders --}}
        @php $othersOrders = $allOrders->whereNotIn('id', $myOrders->pluck('id')); @endphp
        @if ($othersOrders->isNotEmpty())
            <div class="mb-3 mt-6 flex items-center gap-2">
                <h3 class="font-semibold text-ink">Other staff orders</h3>
                <span class="text-xs text-ink-subtle">{{ $othersOrders->count() }} active</span>
            </div>
            @foreach ($othersOrders as $order)
                <article class="card mb-2 p-3 opacity-80">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-mono text-xs font-semibold text-ink-muted">{{ $order->order_number }}</p>
                            <p class="text-xs text-ink-subtle">
                                {{ $order->waiter?->name ?? '—' }} ·
                                {{ $order->table ? 'Table '.$order->table->table_number.' · ' : '' }}
                                {{ $order->guest_label ?? 'Walk-in' }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-ink-subtle">
                                Opened {{ $order->opened_at?->format('H:i') ?? '—' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs',
                                $statusColors[$order->status] ?? 'bg-slate-100 text-slate-600',
                            ])>{{ str_replace('_', ' ', $order->status) }}</span>
                            <p class="text-sm font-semibold text-ink">{{ $currency }} {{ number_format((float) $order->total) }}</p>
                            @can('manage-orders')
                                <a href="{{ route('tenant.pos.manage', $order) }}" class="text-xs text-primary hover:underline">Open</a>
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        @endif
    </section>
</div>
