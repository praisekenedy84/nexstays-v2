<x-layouts.app active-nav="fb-reports" title="F&B Profitability" subtitle="Cost, revenue, and margin by period">

    <form method="GET">
        <x-ui.date-range-filter :from="$from->format('Y-m-d')" :to="$to->format('Y-m-d')">
            @php $ep = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]; @endphp
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('tenant.reports.fb-profitability.export', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-sky-50 hover:text-sky-700 dark:hover:bg-sky-900/20">CSV</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.fb-profitability.export-excel', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20">Excel</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.fb-profitability.export-pdf', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20">PDF</a>
            </div>
        </x-ui.date-range-filter>
    </form>

    @php
        $fmt = fn(float $v) => number_format($v, 0, '.', ',');
        $currency = 'TZS';
        $r = $data;
    @endphp

    {{-- Profitability matrix --}}
    <section class="card mb-6 overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-4">
            <h3 class="font-semibold text-ink">Profitability breakdown</h3>
            <p class="text-xs text-ink-muted mt-0.5">{{ $r['from'] }} — {{ $r['to'] }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-6 py-3 text-left">Line</th>
                        <th class="px-6 py-3 text-right">Food</th>
                        <th class="px-6 py-3 text-right">Drinks</th>
                        <th class="px-6 py-3 text-right font-bold text-ink">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {{-- Revenue --}}
                    <tr class="bg-emerald-50/60">
                        <td class="px-6 py-3 font-medium text-emerald-800">Revenue</td>
                        <td class="px-6 py-3 text-right text-emerald-700">{{ $currency }} {{ $fmt($r['revenue']['food']) }}</td>
                        <td class="px-6 py-3 text-right text-emerald-700">{{ $currency }} {{ $fmt($r['revenue']['drinks']) }}</td>
                        <td class="px-6 py-3 text-right font-bold text-emerald-800">{{ $currency }} {{ $fmt($r['revenue']['total']) }}</td>
                    </tr>
                    {{-- COGS --}}
                    <tr>
                        <td class="px-6 py-3 text-ink-muted">
                            Cost of goods (COGS)
                            <span class="ml-1 text-xs text-ink-subtle">— theoretical, from menu item costs</span>
                        </td>
                        <td class="px-6 py-3 text-right text-rose-600">{{ $currency }} {{ $fmt($r['cogs']['food']) }}</td>
                        <td class="px-6 py-3 text-right text-rose-600">{{ $currency }} {{ $fmt($r['cogs']['drinks']) }}</td>
                        <td class="px-6 py-3 text-right font-semibold text-rose-700">{{ $currency }} {{ $fmt($r['cogs']['total']) }}</td>
                    </tr>
                    {{-- Gross profit --}}
                    <tr class="bg-slate-50">
                        <td class="px-6 py-3 font-semibold text-ink">
                            Gross profit
                            <span class="ml-2 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-normal text-ink-muted">
                                {{ $r['gross_margin_pct'] }}% margin
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right font-semibold {{ ($r['revenue']['food'] - $r['cogs']['food']) >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $currency }} {{ $fmt($r['revenue']['food'] - $r['cogs']['food']) }}
                        </td>
                        <td class="px-6 py-3 text-right font-semibold {{ ($r['revenue']['drinks'] - $r['cogs']['drinks']) >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $currency }} {{ $fmt($r['revenue']['drinks'] - $r['cogs']['drinks']) }}
                        </td>
                        <td class="px-6 py-3 text-right font-bold {{ $r['gross_profit'] >= 0 ? 'text-emerald-800' : 'text-rose-700' }}">
                            {{ $currency }} {{ $fmt($r['gross_profit']) }}
                        </td>
                    </tr>
                    {{-- Purchases --}}
                    <tr>
                        <td class="px-6 py-3 text-ink-muted">
                            Stock purchases received
                            <span class="ml-1 text-xs text-ink-subtle">— POs marked received in period</span>
                        </td>
                        <td class="px-6 py-3 text-right text-amber-700">{{ $currency }} {{ $fmt($r['purchases']['food']) }}</td>
                        <td class="px-6 py-3 text-right text-amber-700">{{ $currency }} {{ $fmt($r['purchases']['drinks']) }}</td>
                        <td class="px-6 py-3 text-right font-semibold text-amber-800">{{ $currency }} {{ $fmt($r['purchases']['total']) }}</td>
                    </tr>
                    {{-- Outlet expenses --}}
                    <tr>
                        <td class="px-6 py-3 text-ink-muted">
                            Outlet expenses
                            <span class="ml-1 text-xs text-ink-subtle">— expenditures linked to F&B outlets</span>
                        </td>
                        <td class="px-6 py-3 text-right text-slate-400" colspan="2">—</td>
                        <td class="px-6 py-3 text-right font-semibold text-amber-800">{{ $currency }} {{ $fmt($r['outlet_expenses']) }}</td>
                    </tr>
                    {{-- Net contribution --}}
                    <tr class="border-t-2 border-slate-300 bg-slate-100/60">
                        <td class="px-6 py-3 font-bold text-ink">Net contribution</td>
                        <td class="px-6 py-3" colspan="2"></td>
                        <td class="px-6 py-3 text-right text-xl font-bold {{ $r['net_contribution'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $currency }} {{ $fmt($r['net_contribution']) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Top selling items --}}
        <section class="card overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="font-semibold text-ink">Top selling items</h3>
                <p class="text-xs text-ink-muted mt-0.5">By revenue, closed orders in period</p>
            </div>

            @if ($r['top_items']->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-ink-subtle">No sales recorded in this period.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            <tr>
                                <th class="px-4 py-2.5 text-left">#</th>
                                <th class="px-4 py-2.5 text-left">Item</th>
                                <th class="px-4 py-2.5 text-right">Qty</th>
                                <th class="px-4 py-2.5 text-right">Revenue</th>
                                <th class="px-4 py-2.5 text-right">COGS</th>
                                <th class="px-4 py-2.5 text-right">Profit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($r['top_items'] as $i => $item)
                                @php $marginOk = $item['profit'] >= 0; @endphp
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-4 py-2.5 text-ink-subtle">{{ $i + 1 }}</td>
                                    <td class="px-4 py-2.5">
                                        <p class="font-medium text-ink leading-snug">{{ $item['name'] }}</p>
                                        <p class="text-ink-subtle leading-snug">{{ $item['category'] }}
                                            <span class="ml-1 rounded bg-slate-100 px-1.5 py-px text-[10px] font-medium uppercase tracking-wide text-ink-muted">{{ $item['outlet_type'] }}</span>
                                        </p>
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-ink">{{ number_format($item['qty_sold']) }}</td>
                                    <td class="px-4 py-2.5 text-right text-emerald-700">{{ $fmt($item['revenue']) }}</td>
                                    <td class="px-4 py-2.5 text-right text-rose-500">{{ $fmt($item['cogs']) }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold {{ $marginOk ? 'text-emerald-700' : 'text-rose-600' }}">
                                        {{ $fmt($item['profit']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="border-t border-slate-100 px-6 py-2.5 text-xs text-ink-subtle">
                    All amounts in {{ $currency }}. COGS is theoretical (menu item cost × qty).
                </p>
            @endif
        </section>

        {{-- Room type performance --}}
        <section class="card overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="font-semibold text-ink">Room type performance</h3>
                <p class="text-xs text-ink-muted mt-0.5">Active reservations checking in during period</p>
            </div>

            @if ($r['top_room_types']->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-ink-subtle">No reservations in this period.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            <tr>
                                <th class="px-4 py-2.5 text-left">Room type</th>
                                <th class="px-4 py-2.5 text-right">Reservations</th>
                                <th class="px-4 py-2.5 text-right">Nights</th>
                                <th class="px-4 py-2.5 text-right">Revenue</th>
                                <th class="px-4 py-2.5 text-right">Avg/night</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php $totalRoomRev = $r['top_room_types']->sum('revenue'); @endphp
                            @foreach ($r['top_room_types'] as $rt)
                                @php
                                    $avgNight = $rt['room_nights'] > 0 ? $rt['revenue'] / $rt['room_nights'] : 0;
                                    $sharePct = $totalRoomRev > 0 ? round($rt['revenue'] / $totalRoomRev * 100) : 0;
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-4 py-2.5">
                                        <p class="font-medium text-ink">{{ $rt['name'] }}</p>
                                        <div class="mt-1 flex items-center gap-1.5">
                                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-200">
                                                <div class="h-full rounded-full bg-primary" style="width: {{ $sharePct }}%"></div>
                                            </div>
                                            <span class="text-ink-subtle">{{ $sharePct }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-ink">{{ number_format($rt['reservations']) }}</td>
                                    <td class="px-4 py-2.5 text-right text-ink">{{ number_format($rt['room_nights']) }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-emerald-700">{{ $fmt($rt['revenue']) }}</td>
                                    <td class="px-4 py-2.5 text-right text-ink-muted">{{ $fmt($avgNight) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200 bg-slate-50">
                            <tr>
                                <td class="px-4 py-2.5 font-bold text-ink">Total</td>
                                <td class="px-4 py-2.5 text-right font-semibold">{{ $r['top_room_types']->sum('reservations') }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold">{{ $r['top_room_types']->sum('room_nights') }}</td>
                                <td class="px-4 py-2.5 text-right font-bold text-emerald-700">{{ $fmt($totalRoomRev) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="border-t border-slate-100 px-6 py-2.5 text-xs text-ink-subtle">
                    All amounts in {{ $currency }}. Excludes cancelled and no-show reservations.
                </p>
            @endif
        </section>

    </div>

</x-layouts.app>
