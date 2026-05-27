<x-layouts.app active-nav="payment-summary-report" title="Payment collection summary" subtitle="All payments received, broken down by method">

    <form method="GET">
        <x-ui.date-range-filter :from="$from->format('Y-m-d')" :to="$to->format('Y-m-d')">
            @php $ep = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]; @endphp
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('tenant.reports.payment-summary.export', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-sky-50 hover:text-sky-700 dark:hover:bg-sky-900/20">CSV</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.payment-summary.export-excel', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20">Excel</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.payment-summary.export-pdf', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20">PDF</a>
            </div>
        </x-ui.date-range-filter>
    </form>

    @php
        $currency = config('nexstay.currency.default', 'TZS');
        $fmt      = fn (float $v) => number_format($v, 0);

        $methodLabels = [
            'cash'         => 'Cash',
            'card'         => 'Card',
            'mobile_money' => 'Mobile money',
        ];
        $methodColors = [
            'cash'         => 'bg-emerald-100 text-emerald-800',
            'card'         => 'bg-violet-100 text-violet-800',
            'mobile_money' => 'bg-sky-100 text-sky-800',
        ];
    @endphp

    {{-- KPI row --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.kpi-card label="Total collected" :value="$currency.' '.$fmt($report['grand_total'])" accent="green" />
        <x-ui.kpi-card label="Folio payments" :value="$currency.' '.$fmt($report['folio_total'])"
            subtitle="Posted to room accounts" accent="blue" />
        <x-ui.kpi-card label="Direct POS" :value="$currency.' '.$fmt($report['direct_total'])"
            subtitle="Cash / mobile / card at outlet" accent="sky" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- By method table --}}
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="font-semibold text-ink">Breakdown by method</h3>
                <p class="mt-0.5 text-xs text-ink-muted">{{ $report['from'] }} — {{ $report['to'] }}</p>
            </div>

            @if ($report['by_method']->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-ink-muted">No payments recorded in this period.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <tr>
                            <th class="px-6 py-3 text-left">Method</th>
                            <th class="px-6 py-3 text-right">Transactions</th>
                            <th class="px-6 py-3 text-right">Amount</th>
                            <th class="px-6 py-3 text-right">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($report['by_method'] as $row)
                            @php
                                $share = $report['grand_total'] > 0
                                    ? round($row['total'] / $report['grand_total'] * 100, 1)
                                    : 0;
                                $label = $methodLabels[$row['method']] ?? ucfirst(str_replace('_', ' ', $row['method']));
                                $color = $methodColors[$row['method']] ?? 'bg-slate-100 text-slate-700';
                            @endphp
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $color }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right text-ink-muted">{{ number_format($row['count']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-ink">
                                    {{ $currency }} {{ $fmt($row['total']) }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <div class="w-14 overflow-hidden rounded-full bg-slate-200 h-1.5">
                                            <div class="h-full rounded-full bg-primary" style="width: {{ $share }}%"></div>
                                        </div>
                                        <span class="w-8 text-right text-xs text-ink-muted">{{ $share }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50 text-xs font-semibold">
                        <tr>
                            <td class="px-6 py-3 font-bold text-ink">Total</td>
                            <td class="px-6 py-3 text-right">{{ number_format($report['by_method']->sum('count')) }}</td>
                            <td class="px-6 py-3 text-right font-bold text-emerald-700">
                                {{ $currency }} {{ $fmt($report['grand_total']) }}
                            </td>
                            <td class="px-6 py-3 text-right">100%</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        {{-- Folio vs Direct split --}}
        <div class="card p-6 space-y-4">
            <div>
                <h3 class="font-semibold text-ink">Channel split</h3>
                <p class="mt-0.5 text-xs text-ink-muted">Room folio charges vs direct POS collection</p>
            </div>

            @php
                $total       = $report['grand_total'];
                $folioPct    = $total > 0 ? round($report['folio_total']  / $total * 100, 1) : 0;
                $directPct   = $total > 0 ? round($report['direct_total'] / $total * 100, 1) : 0;
            @endphp

            <div class="space-y-3">
                <div>
                    <div class="mb-1 flex justify-between text-xs text-ink-muted">
                        <span>Folio (room account)</span>
                        <span>{{ $folioPct }}%</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $folioPct }}%"></div>
                    </div>
                    <p class="mt-0.5 text-right text-sm font-semibold text-indigo-700">
                        {{ $currency }} {{ $fmt($report['folio_total']) }}
                    </p>
                </div>

                <div>
                    <div class="mb-1 flex justify-between text-xs text-ink-muted">
                        <span>Direct POS</span>
                        <span>{{ $directPct }}%</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $directPct }}%"></div>
                    </div>
                    <p class="mt-0.5 text-right text-sm font-semibold text-emerald-700">
                        {{ $currency }} {{ $fmt($report['direct_total']) }}
                    </p>
                </div>
            </div>

            @if ($total > 0)
                <div class="rounded-lg bg-slate-50 p-3 text-xs text-ink-muted">
                    Total collected in period:
                    <span class="font-semibold text-ink">{{ $currency }} {{ $fmt($total) }}</span>
                </div>
            @endif
        </div>

    </div>

</x-layouts.app>
