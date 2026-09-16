@php
    $typeLabels = [
        'restock' => 'Restock',
        'purchase' => 'Purchase',
        'consumption' => 'Consumption',
        'stock_shortage' => 'Shortage',
    ];
    $filterTypes = [
        '' => 'All movements',
        'restock' => 'Restocks',
        'purchase' => 'Purchases',
        'consumption' => 'Consumption',
        'stock_shortage' => 'Shortages',
    ];
@endphp

<x-layouts.app active-nav="inventory" title="Stock history" :subtitle="$stockItem->name">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('tenant.stock-items.index', ['outlet_id' => $stockItem->outlet_id]) }}" class="text-sm text-primary hover:underline">← Inventory</a>
        <div class="flex flex-wrap gap-2">
            @can('manage-inventory')
                <a href="{{ route('tenant.stock-items.restock-form', $stockItem) }}" class="btn-primary text-sm">Restock</a>
            @endcan
            <a href="{{ route('tenant.stock-items.movements', ['search' => $stockItem->name]) }}" class="btn-outline text-sm">All stock history</a>
        </div>
    </div>

    <div class="card mb-6 max-w-2xl p-5">
        <h2 class="text-lg font-semibold text-ink">{{ $stockItem->name }}</h2>
        <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-xs text-ink-muted">Current stock</dt>
                <dd class="font-medium">{{ $stockItem->current_stock }} {{ $stockItem->unit }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink-muted">Outlet</dt>
                <dd>{{ $stockItem->outlet?->name ?? 'Property' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink-muted">Last restocked</dt>
                <dd>
                    @if ($stockItem->last_restocked_at)
                        {{ $stockItem->last_restocked_at->format('d M Y H:i') }}
                        @if ($stockItem->lastRestockedBy)
                            <span class="text-ink-muted">by {{ $stockItem->lastRestockedBy->name }}</span>
                        @endif
                    @else
                        <span class="text-ink-subtle">Never</span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    <form method="GET" class="mb-4">
        <label class="mb-1 block text-xs font-medium text-ink-muted">Filter by type</label>
        <select name="type" class="input-field w-full max-w-xs" onchange="this.form.submit()">
            @foreach ($filterTypes as $value => $label)
                <option value="{{ $value }}" @selected(($type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <div class="card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3">When</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3 text-right">Quantity</th>
                    <th class="px-5 py-3">Performed by</th>
                    <th class="px-5 py-3">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($movements as $movement)
                    @php
                        $qty = (float) $movement->quantity;
                        $isIn = $qty > 0;
                    @endphp
                    <tr>
                        <td class="px-5 py-4 text-ink-muted whitespace-nowrap">
                            {{ $movement->created_at?->format('d M Y H:i') ?? '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <span @class([
                                'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                'bg-emerald-50 text-emerald-800' => in_array($movement->movement_type, ['restock', 'purchase'], true),
                                'bg-slate-100 text-slate-700' => $movement->movement_type === 'consumption',
                                'bg-amber-50 text-amber-800' => $movement->movement_type === 'stock_shortage',
                            ])>
                                {{ $typeLabels[$movement->movement_type] ?? $movement->movement_type }}
                            </span>
                        </td>
                        <td @class([
                            'px-5 py-4 text-right font-medium tabular-nums whitespace-nowrap',
                            'text-emerald-700' => $isIn,
                            'text-red-700' => ! $isIn,
                        ])>
                            {{ $isIn ? '+' : '' }}{{ $movement->quantity }} {{ $stockItem->unit }}
                        </td>
                        <td class="px-5 py-4">
                            {{ $movement->performer?->name ?? 'System' }}
                        </td>
                        <td class="px-5 py-4 text-ink-muted">
                            {{ $movement->notes ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-ink-muted">No stock movements recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $movements->links() }}</div>
    </div>
</x-layouts.app>
