<x-layouts.app active-nav="purchases" :title="$order->po_number">
    <div class="mb-6 flex flex-wrap justify-between gap-3">
        <a href="{{ route('tenant.purchases.index') }}" class="text-sm text-primary hover:underline">← Purchases</a>
        @can('manage-purchases')
            @if ($order->isReceivable())
                <form method="POST" action="{{ route('tenant.purchases.receive', $order) }}">
                    @csrf
                    <button type="submit" class="btn-primary">Receive stock</button>
                </form>
            @endif
        @endcan
    </div>

    <div class="card mb-6 p-6">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <p class="font-mono text-lg font-bold text-primary">{{ $order->po_number }}</p>
                <p class="text-ink-muted">{{ $order->supplier_name }} · {{ ucfirst($order->department) }}</p>
            </div>
            <x-ui.status-badge :status="$order->status" />
        </div>
        <p class="mt-4 text-2xl font-bold">@money($order->total_amount)</p>
        @if ($order->notes)
            <p class="mt-2 text-sm text-ink-muted">{{ $order->notes }}</p>
        @endif
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3">Item</th>
                    <th class="px-5 py-3 text-right">Qty</th>
                    <th class="px-5 py-3 text-right">Unit cost</th>
                    <th class="px-5 py-3 text-right">Line total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($order->lines as $line)
                    <tr>
                        <td class="px-5 py-4">{{ $line->stockItem?->name }}</td>
                        <td class="px-5 py-4 text-right">{{ $line->quantity }}</td>
                        <td class="px-5 py-4 text-right">@money($line->unit_cost)</td>
                        <td class="px-5 py-4 text-right font-medium">@money($line->line_total)</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
