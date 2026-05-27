<x-layouts.app active-nav="purchases" :title="'Receive ' . $order->po_number">

    <div class="mb-6">
        <a href="{{ route('tenant.purchases.show', $order) }}" class="text-sm text-primary hover:underline">← {{ $order->po_number }}</a>
    </div>

    {{-- PO summary --}}
    <div class="card mb-6 p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-mono text-lg font-bold text-primary">{{ $order->po_number }}</p>
                <p class="text-sm text-ink-muted">{{ $order->supplier_name }}
                    @if ($order->supplier_reference) · ref: {{ $order->supplier_reference }}@endif
                </p>
            </div>
            <x-ui.status-badge :status="$order->status" />
        </div>
        @if ($order->delivery_date_expected)
            <p class="mt-2 text-xs text-ink-muted">Expected delivery: <strong>{{ $order->delivery_date_expected->format('d M Y') }}</strong></p>
        @endif
        @if ($order->payment_terms)
            <p class="mt-1 text-xs text-ink-muted">Payment terms: {{ $order->payment_terms }}</p>
        @endif
    </div>

    <form method="POST" action="{{ route('tenant.purchases.receive', $order) }}">
        @csrf

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-ink">Enter actual goods received</h2>
                <p class="mt-0.5 text-xs text-ink-muted">Confirm quantities and costs delivered. Leave unchanged if they match the order exactly.</p>
            </div>

            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-5 py-3 text-left">Item</th>
                        <th class="px-5 py-3 text-right">Ordered qty</th>
                        <th class="px-5 py-3 text-right">Ordered cost</th>
                        <th class="px-5 py-3 text-right">Received qty <span class="text-red-500">*</span></th>
                        <th class="px-5 py-3 text-right">Actual unit cost <span class="text-red-500">*</span></th>
                        <th class="px-5 py-3 text-left">Notes / discrepancy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="receive-body">
                    @foreach ($order->lines as $i => $line)
                        <input type="hidden" name="lines[{{ $i }}][line_id]" value="{{ $line->id }}">
                        <tr class="receive-row hover:bg-slate-50/60">
                            <td class="px-5 py-4 font-medium">{{ $line->stockItem?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-right text-ink-muted">{{ rtrim(rtrim((string) $line->quantity, '0'), '.') }}</td>
                            <td class="px-5 py-4 text-right text-ink-muted">@money($line->unit_cost)</td>
                            <td class="px-5 py-4">
                                <input type="number" step="0.001" min="0"
                                       name="lines[{{ $i }}][qty_received]"
                                       value="{{ old("lines.{$i}.qty_received", $line->quantity) }}"
                                       class="input-field text-right recv-qty"
                                       data-ordered="{{ $line->quantity }}"
                                       required>
                            </td>
                            <td class="px-5 py-4">
                                <input type="number" step="0.01" min="0"
                                       name="lines[{{ $i }}][actual_unit_cost]"
                                       value="{{ old("lines.{$i}.actual_unit_cost", $line->unit_cost) }}"
                                       class="input-field text-right recv-cost"
                                       data-ordered-cost="{{ $line->unit_cost }}"
                                       required>
                            </td>
                            <td class="px-5 py-4">
                                <input type="text"
                                       name="lines[{{ $i }}][line_notes]"
                                       value="{{ old("lines.{$i}.line_notes") }}"
                                       placeholder="e.g. 2 units missing, price increase"
                                       class="input-field text-sm">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-200 bg-slate-50">
                    <tr>
                        <td colspan="2" class="px-5 py-3 text-right text-sm font-semibold text-ink-muted">Ordered total</td>
                        <td class="px-5 py-3 text-right font-semibold text-ink">@money($order->total_amount)</td>
                        <td colspan="2" class="px-5 py-3 text-right text-sm font-semibold text-ink-muted">Received total (live)</td>
                        <td class="px-5 py-3 font-bold text-ink" id="recv-total">—</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-primary">Confirm receipt & update stock</button>
            <a href="{{ route('tenant.purchases.show', $order) }}" class="btn-outline">Cancel</a>
        </div>
    </form>

    <script>
    (function () {
        const body = document.getElementById('receive-body');
        const recvTotal = document.getElementById('recv-total');

        function recalc() {
            let total = 0;
            body.querySelectorAll('.receive-row').forEach(row => {
                const qty  = parseFloat(row.querySelector('.recv-qty')?.value)  || 0;
                const cost = parseFloat(row.querySelector('.recv-cost')?.value) || 0;
                total += qty * cost;
            });
            recvTotal.textContent = Number(total).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        body.addEventListener('input', recalc);
        recalc();
    })();
    </script>

</x-layouts.app>
