<x-layouts.app active-nav="sales-summary-report" title="Sales summary" subtitle="Posted sales by division — daily, weekly, and monthly">

    @php
        $currency = config('nexstay.currency.default', 'TZS');
        $fmt      = fn (float $v) => number_format($v, 0);
        $ep       = ['period' => $period, 'date' => $date->format('Y-m-d')];
        $summary  = $report['summary'];
        $periods  = [
            'daily'   => 'Daily',
            'weekly'  => 'Weekly',
            'monthly' => 'Monthly',
        ];
    @endphp

    <form method="GET" class="mb-6 space-y-4">
        <div class="flex flex-wrap gap-1.5">
            @foreach ($periods as $key => $label)
                <a href="{{ route('tenant.reports.sales-summary', ['period' => $key, 'date' => $date->format('Y-m-d')]) }}"
                   @class([
                       'rounded-full border px-3 py-1 text-xs font-medium transition',
                       'border-primary bg-primary/10 text-primary' => $period === $key,
                       'border-slate-200 text-ink-muted hover:border-primary/40 hover:text-primary' => $period !== $key,
                   ])>
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-ink-muted">
                    @if ($period === 'monthly')
                        Month
                    @elseif ($period === 'weekly')
                        Week containing
                    @else
                        Date
                    @endif
                </label>
                <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="input-field w-auto" onchange="this.form.submit()">
            </div>
            <input type="hidden" name="period" value="{{ $period }}">

            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('tenant.reports.sales-summary.export-excel', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20">Excel</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.sales-summary.export-pdf', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20">PDF</a>
            </div>

            <a href="{{ route('tenant.reports') }}" class="btn-outline">All reports</a>
        </div>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.kpi-card label="Total posted sales" :value="$currency.' '.$fmt($summary['total'])" accent="green" />
        <x-ui.kpi-card label="Payments collected" :value="$currency.' '.$fmt($summary['payments_collected'])" accent="blue" />
        <x-ui.kpi-card label="Room nights occupied" :value="(string) $summary['room_nights']"
            :subtitle="$report['period_label']" accent="sky" />
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-4">
            <h3 class="font-semibold text-ink">Division breakdown</h3>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $report['period_label'] }} · {{ $report['from'] }} — {{ $report['to'] }}</p>
        </div>

        <div class="grid divide-y divide-slate-100 sm:grid-cols-4 sm:divide-x sm:divide-y-0">
            @php
                $divisions = [
                    ['label' => 'Rooms (posted)', 'value' => $summary['rooms'],      'color' => 'text-indigo-700',  'bg' => 'bg-indigo-50'],
                    ['label' => 'Restaurant',     'value' => $summary['restaurant'], 'color' => 'text-amber-700',   'bg' => 'bg-amber-50'],
                    ['label' => 'Bar & lounge',   'value' => $summary['bar'],       'color' => 'text-sky-700',     'bg' => 'bg-sky-50'],
                    ['label' => 'Ancillary',      'value' => $summary['ancillary'],  'color' => 'text-violet-700',  'bg' => 'bg-violet-50'],
                ];
                $maxValue = max(1, $summary['total']);
            @endphp

            @foreach ($divisions as $div)
                @php $pct = min(100, round($div['value'] / $maxValue * 100)); @endphp
                <div class="px-5 py-4">
                    <p class="mb-1 text-xs font-medium text-ink-muted">{{ $div['label'] }}</p>
                    <p class="text-base font-bold {{ $div['color'] }}">{{ $currency }} {{ $fmt($div['value']) }}</p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full {{ $div['bg'] }} border border-current {{ $div['color'] }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($report['daily_rows'] !== [])
        <div class="card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="font-semibold text-ink">Daily breakdown</h3>
                <p class="mt-0.5 text-xs text-ink-muted">Posted sales per day in this period</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <tr>
                            <th class="px-6 py-3 text-left">Date</th>
                            <th class="px-6 py-3 text-right">Rooms</th>
                            <th class="px-6 py-3 text-right">Restaurant</th>
                            <th class="px-6 py-3 text-right">Bar</th>
                            <th class="px-6 py-3 text-right">Ancillary</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3 text-right">Payments</th>
                            <th class="px-6 py-3 text-right">Room nights</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($report['daily_rows'] as $row)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-3 font-medium text-ink">{{ $row['date_label'] }}</td>
                                <td class="px-6 py-3 text-right text-indigo-700">{{ $fmt($row['rooms']) }}</td>
                                <td class="px-6 py-3 text-right text-amber-700">{{ $fmt($row['restaurant']) }}</td>
                                <td class="px-6 py-3 text-right text-sky-700">{{ $fmt($row['bar']) }}</td>
                                <td class="px-6 py-3 text-right text-violet-700">{{ $fmt($row['ancillary']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-ink">{{ $fmt($row['total']) }}</td>
                                <td class="px-6 py-3 text-right text-emerald-700">{{ $fmt($row['payments_collected']) }}</td>
                                <td class="px-6 py-3 text-right text-ink-muted">{{ $row['room_nights'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50 text-xs font-semibold">
                        <tr>
                            <td class="px-6 py-3 font-bold text-ink">Period total</td>
                            <td class="px-6 py-3 text-right text-indigo-700">{{ $fmt($summary['rooms']) }}</td>
                            <td class="px-6 py-3 text-right text-amber-700">{{ $fmt($summary['restaurant']) }}</td>
                            <td class="px-6 py-3 text-right text-sky-700">{{ $fmt($summary['bar']) }}</td>
                            <td class="px-6 py-3 text-right text-violet-700">{{ $fmt($summary['ancillary']) }}</td>
                            <td class="px-6 py-3 text-right font-bold text-ink">{{ $fmt($summary['total']) }}</td>
                            <td class="px-6 py-3 text-right font-bold text-emerald-700">{{ $fmt($summary['payments_collected']) }}</td>
                            <td class="px-6 py-3 text-right font-bold text-ink">{{ $summary['room_nights'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

</x-layouts.app>
