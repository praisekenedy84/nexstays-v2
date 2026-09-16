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

<x-layouts.app active-nav="stock-history" title="Stock history" subtitle="Who restocked, how much, and when">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            @if ($barOutlets->isNotEmpty())
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-muted">Bar outlet</label>
                    <select name="outlet_id" class="input-field w-full min-w-0 sm:min-w-[180px]" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach ($barOutlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected($outletId === $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-muted">Type</label>
                <select name="type" class="input-field w-full min-w-0 sm:min-w-[160px]" onchange="this.form.submit()">
                    @foreach ($filterTypes as $value => $label)
                        <option value="{{ $value }}" @selected(($type ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-ui.search-bar :value="$search" placeholder="Item name…" />
        </form>
        <a href="{{ route('tenant.stock-items.index', ['outlet_id' => $outletId]) }}" class="btn-outline text-sm">Stock items</a>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3">When</th>
                    <th class="px-5 py-3">Item</th>
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
                        $item = $movement->stockItem;
                    @endphp
                    <tr>
                        <td class="px-5 py-4 text-ink-muted whitespace-nowrap">
                            {{ $movement->created_at?->format('d M Y H:i') ?? '—' }}
                        </td>
                        <td class="px-5 py-4">
                            @if ($item)
                                <a href="{{ route('tenant.stock-items.history', $item) }}" class="font-medium text-primary hover:underline">
                                    {{ $item->name }}
                                </a>
                                <span class="mt-0.5 block text-xs text-ink-subtle">{{ $item->outlet?->name ?? 'Property' }}</span>
                            @else
                                <span class="text-ink-muted">Deleted item</span>
                            @endif
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
                            {{ $isIn ? '+' : '' }}{{ $movement->quantity }}{{ $item ? ' '.$item->unit : '' }}
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
                        <td colspan="6" class="px-5 py-12 text-center text-ink-muted">No stock movements found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $movements->links() }}</div>
    </div>
</x-layouts.app>
