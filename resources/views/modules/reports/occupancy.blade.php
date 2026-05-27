<x-layouts.app active-nav="occupancy-report" title="Occupancy report" subtitle="Room occupancy, ADR, and RevPAR by day">

    <form method="GET">
        <x-ui.date-range-filter :from="$from->format('Y-m-d')" :to="$to->format('Y-m-d')">
            @php $ep = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]; @endphp
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('tenant.reports.occupancy.export', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-sky-50 hover:text-sky-700 dark:hover:bg-sky-900/20">CSV</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.occupancy.export-excel', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20">Excel</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.occupancy.export-pdf', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20">PDF</a>
            </div>
        </x-ui.date-range-filter>
    </form>

    @php
        $currency = config('nexstay.currency.default', 'TZS');
        $fmt      = fn (float $v) => number_format($v, 0);
        $t        = $report['totals'];
    @endphp

    {{-- KPI summary --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <x-ui.kpi-card label="Rooms available" :value="$t['rooms_available']" />
        <x-ui.kpi-card label="Room nights sold" :value="number_format($t['room_nights_sold'])" accent="blue" />
        <x-ui.kpi-card label="Avg occupancy" :value="$t['avg_occupancy_pct'].'%'" accent="sky" />
        <x-ui.kpi-card label="Total revenue" :value="$currency.' '.$fmt($t['total_revenue'])" accent="green" />
        <x-ui.kpi-card label="ADR" :value="$currency.' '.$fmt($t['adr'])"
            subtitle="Avg daily rate per occupied room" />
        <x-ui.kpi-card label="RevPAR" :value="$currency.' '.$fmt($t['revpar'])"
            subtitle="Revenue per available room" />
    </div>

    {{-- Daily breakdown --}}
    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-4">
            <h3 class="font-semibold text-ink">Daily breakdown</h3>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $report['from'] }} — {{ $report['to'] }} · {{ $report['days'] }} days</p>
        </div>

        @if (empty($report['rows']))
            <p class="px-6 py-10 text-center text-sm text-ink-muted">No data for this period.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <tr>
                            <th class="px-5 py-3 text-left">Date</th>
                            <th class="px-5 py-3 text-right">Available</th>
                            <th class="px-5 py-3 text-right">Occupied</th>
                            <th class="px-5 py-3 text-right">Occupancy</th>
                            <th class="px-5 py-3 text-right">Revenue</th>
                            <th class="px-5 py-3 text-right">ADR</th>
                            <th class="px-5 py-3 text-right">RevPAR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($report['rows'] as $row)
                            @php
                                $occ = $row['occupancy_pct'];
                                $barColor = $occ >= 80 ? 'bg-emerald-500'
                                          : ($occ >= 50 ? 'bg-amber-400' : 'bg-rose-400');
                            @endphp
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3 font-medium text-ink">
                                    {{ \Carbon\Carbon::parse($row['date'])->format('D, d M') }}
                                </td>
                                <td class="px-5 py-3 text-right text-ink-muted">{{ $row['rooms_available'] }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-ink">{{ $row['rooms_occupied'] }}</td>
                                <td class="px-5 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <div class="w-16 overflow-hidden rounded-full bg-slate-200 h-1.5">
                                            <div class="h-full rounded-full {{ $barColor }}"
                                                 style="width: {{ min($occ, 100) }}%"></div>
                                        </div>
                                        <span class="w-10 text-right font-semibold text-ink">{{ $occ }}%</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right text-emerald-700">{{ $fmt((float) $row['revenue']) }}</td>
                                <td class="px-5 py-3 text-right text-ink-muted">{{ $fmt((float) $row['adr']) }}</td>
                                <td class="px-5 py-3 text-right text-ink-muted">{{ $fmt((float) $row['revpar']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50 text-xs font-semibold">
                        <tr>
                            <td class="px-5 py-3 font-bold text-ink">Total / Avg</td>
                            <td class="px-5 py-3 text-right">{{ $t['rooms_available'] }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($t['room_nights_sold']) }}</td>
                            <td class="px-5 py-3 text-right font-bold text-ink">{{ $t['avg_occupancy_pct'] }}%</td>
                            <td class="px-5 py-3 text-right font-bold text-emerald-700">{{ $fmt($t['total_revenue']) }}</td>
                            <td class="px-5 py-3 text-right font-bold">{{ $fmt($t['adr']) }}</td>
                            <td class="px-5 py-3 text-right font-bold">{{ $fmt($t['revpar']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="border-t border-slate-100 px-6 py-2.5 text-xs text-ink-muted">
                All amounts in {{ $currency }}.
                ADR = revenue ÷ rooms occupied.
                RevPAR = revenue ÷ rooms available.
                Occupancy is based on confirmed, checked-in, and checked-out reservations.
            </p>
        @endif
    </div>

</x-layouts.app>
