<x-layouts.app active-nav="restaurant" :title="$outlet->name" subtitle="Restaurant POS — tables & active orders">
    <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
        <section class="card p-5">
            <h2 class="font-bold text-ink">Tables</h2>
            <div class="mt-4 grid grid-cols-2 gap-3">
                @foreach ($tables as $table)
                    <div @class([
                        'rounded-xl border p-3 text-center',
                        $table->status === 'occupied' ? 'border-primary bg-primary-soft' : 'border-slate-200 bg-slate-50',
                    ])>
                        <p class="text-lg font-bold">{{ $table->table_number }}</p>
                        <p class="text-xs capitalize text-ink-muted">{{ str_replace('_', ' ', $table->status) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-ink">Active orders</h2>
                @can('manage-orders')
                    <code class="text-xs text-ink-subtle">POST /api/v1/outlets/{{ $outlet->id }}/orders</code>
                @endcan
            </div>
            @forelse ($orders as $order)
                <article class="card p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-mono text-sm font-semibold text-primary">{{ $order->order_number }}</p>
                            <p class="text-sm text-ink-muted">
                                Table {{ $order->table?->table_number ?? '—' }} · {{ $order->covers }} covers
                            </p>
                        </div>
                        <x-ui.status-badge :status="$order->status" />
                    </div>
                    <p class="mt-2 text-lg font-bold">@money($order->total)</p>
                </article>
            @empty
                <p class="text-ink-muted">No open orders.</p>
            @endforelse
        </section>
    </div>
</x-layouts.app>
