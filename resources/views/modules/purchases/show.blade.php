<x-layouts.app active-nav="purchases" :title="$order->po_number">

    {{-- Toolbar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('tenant.purchases.index') }}" class="text-sm text-primary hover:underline">← Purchases</a>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tenant.purchases.print', $order) }}" target="_blank" class="btn-outline text-xs">
                Print / PDF
            </a>
            @can('manage-purchases')
                @if ($order->status === 'draft')
                    <form method="POST" action="{{ route('tenant.purchases.mark-ordered', $order) }}">
                        @csrf
                        <button type="submit" class="btn-outline text-xs">Mark as ordered</button>
                    </form>
                @endif
                @if ($order->isReceivable())
                    <a href="{{ route('tenant.purchases.receive-form', $order) }}" class="btn-primary text-xs">
                        Receive goods →
                    </a>
                @endif
            @endcan
        </div>
    </div>

    {{-- Summary card --}}
    <div class="card mb-6 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xl font-bold text-primary">{{ $order->po_number }}</p>
                <p class="mt-0.5 text-ink-muted">{{ $order->supplier_name }}
                    @if ($order->supplier_reference) · <span class="text-xs">ref: {{ $order->supplier_reference }}</span>@endif
                </p>
                <p class="mt-0.5 text-xs text-ink-muted">{{ ucfirst($order->department) }}
                    @if ($order->outlet) · {{ $order->outlet->name }}@endif
                </p>
            </div>
            <x-ui.status-badge :status="$order->status" />
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-4">
            <div>
                <p class="text-xs text-ink-muted">Ordered total</p>
                <p class="text-xl font-bold text-ink">@money($order->total_amount)</p>
            </div>
            @if ($order->received_total !== null)
                <div>
                    <p class="text-xs text-ink-muted">Received total</p>
                    <p class="text-xl font-bold text-ink">@money($order->received_total)</p>
                </div>
                <div>
                    @php $variance = $order->costVariance(); $variancePct = $order->costVariancePct(); @endphp
                    <p class="text-xs text-ink-muted">Cost variance</p>
                    <p class="text-xl font-bold {{ $variance > 0 ? 'text-red-600' : ($variance < 0 ? 'text-emerald-600' : 'text-ink') }}">
                        {{ $variance > 0 ? '+' : '' }}@money($variance)
                        @if ($variancePct !== null)
                            <span class="text-sm font-normal">({{ $variance > 0 ? '+' : '' }}{{ number_format($variancePct, 1) }}%)</span>
                        @endif
                    </p>
                </div>
            @endif
            <div>
                <p class="text-xs text-ink-muted">Created by</p>
                <p class="text-sm font-medium">{{ $order->creator?->name ?? '—' }}</p>
            </div>
        </div>

        {{-- Status timeline --}}
        <div class="mt-6 flex flex-wrap gap-6">
            <div class="text-center">
                <div class="mx-auto mb-1 flex h-8 w-8 items-center justify-center rounded-full {{ $order->created_at ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }} text-xs font-bold">✓</div>
                <p class="text-xs font-medium text-ink">Created</p>
                <p class="text-xs text-ink-muted">{{ $order->created_at?->format('d M Y') }}</p>
            </div>
            <div class="flex items-center self-start pt-3 text-slate-300">—</div>
            <div class="text-center">
                <div class="mx-auto mb-1 flex h-8 w-8 items-center justify-center rounded-full {{ $order->ordered_at ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }} text-xs font-bold">
                    {{ $order->ordered_at ? '✓' : '2' }}
                </div>
                <p class="text-xs font-medium text-ink">Ordered</p>
                <p class="text-xs text-ink-muted">{{ $order->ordered_at?->format('d M Y') ?? '—' }}</p>
            </div>
            @if ($order->delivery_date_expected)
                <div class="flex items-center self-start pt-3 text-slate-300">—</div>
                <div class="text-center">
                    <div class="mx-auto mb-1 flex h-8 w-8 items-center justify-center rounded-full bg-amber-50 text-amber-600 text-xs font-bold">📅</div>
                    <p class="text-xs font-medium text-ink">Expected</p>
                    <p class="text-xs text-ink-muted">{{ $order->delivery_date_expected->format('d M Y') }}</p>
                </div>
            @endif
            <div class="flex items-center self-start pt-3 text-slate-300">—</div>
            <div class="text-center">
                <div class="mx-auto mb-1 flex h-8 w-8 items-center justify-center rounded-full {{ $order->received_at ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }} text-xs font-bold">
                    {{ $order->received_at ? '✓' : '3' }}
                </div>
                <p class="text-xs font-medium text-ink">Received</p>
                <p class="text-xs text-ink-muted">{{ $order->received_at?->format('d M Y') ?? '—' }}</p>
            </div>
        </div>

        @if ($order->payment_terms || $order->notes)
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @if ($order->payment_terms)
                    <p class="text-xs text-ink-muted"><span class="font-semibold">Payment terms:</span> {{ $order->payment_terms }}</p>
                @endif
                @if ($order->notes)
                    <p class="text-xs text-ink-muted"><span class="font-semibold">Notes:</span> {{ $order->notes }}</p>
                @endif
            </div>
        @endif
    </div>

    {{-- Line items --}}
    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-3">
            <h3 class="text-sm font-semibold text-ink">Line items</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                <tr>
                    <th class="px-5 py-3 text-left">Item</th>
                    <th class="px-5 py-3 text-right">Ordered qty</th>
                    <th class="px-5 py-3 text-right">Unit cost</th>
                    <th class="px-5 py-3 text-right">Line total</th>
                    @if ($order->status === 'received')
                        <th class="px-5 py-3 text-right">Received qty</th>
                        <th class="px-5 py-3 text-right">Actual cost</th>
                        <th class="px-5 py-3 text-right">Variance</th>
                    @endif
                    <th class="px-5 py-3 text-left">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($order->lines as $line)
                    @php $vp = $order->status === 'received' ? $line->variancePct() : null; @endphp
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-4 font-medium">{{ $line->stockItem?->name ?? '—' }}</td>
                        <td class="px-5 py-4 text-right">{{ rtrim(rtrim((string) $line->quantity, '0'), '.') }}</td>
                        <td class="px-5 py-4 text-right">@money($line->unit_cost)</td>
                        <td class="px-5 py-4 text-right font-semibold">@money($line->line_total)</td>
                        @if ($order->status === 'received')
                            <td class="px-5 py-4 text-right">{{ $line->qty_received !== null ? rtrim(rtrim((string) $line->qty_received, '0'), '.') : '—' }}</td>
                            <td class="px-5 py-4 text-right">@if ($line->actual_unit_cost !== null)@money($line->actual_unit_cost)@else —@endif</td>
                            <td class="px-5 py-4 text-right">
                                @if ($vp !== null)
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $vp > 0 ? 'bg-red-100 text-red-700' : ($vp < 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600') }}">
                                        {{ $vp > 0 ? '+' : '' }}{{ number_format($vp, 1) }}%
                                    </span>
                                @else
                                    <span class="text-ink-subtle">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="px-5 py-4 text-xs text-ink-muted">{{ $line->line_notes ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-slate-200 bg-slate-50 text-sm font-semibold">
                <tr>
                    <td colspan="{{ $order->status === 'received' ? 3 : 2 }}" class="px-5 py-3 text-right text-ink-muted">Ordered total</td>
                    <td class="px-5 py-3 text-right text-ink">@money($order->total_amount)</td>
                    @if ($order->status === 'received')
                        <td colspan="2" class="px-5 py-3 text-right text-ink-muted">Received total</td>
                        <td class="px-5 py-3 text-right {{ $order->costVariance() > 0 ? 'text-red-700' : ($order->costVariance() < 0 ? 'text-emerald-700' : 'text-ink') }}">
                            @money($order->received_total)
                        </td>
                    @endif
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

</x-layouts.app>
