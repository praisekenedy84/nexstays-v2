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
        $reportFeatures = $reportFeatures ?? \App\Support\TenantFeatures::reportFlags();
        $divisions = [];
        if ($reportFeatures['hotel']) {
            $divisions[] = ['label' => 'Rooms (posted)', 'value' => $summary['rooms'], 'color' => 'text-indigo-700', 'bg' => 'bg-indigo-50'];
        }
        if ($reportFeatures['restaurant']) {
            $divisions[] = ['label' => 'Restaurant', 'value' => $summary['restaurant'], 'color' => 'text-amber-700', 'bg' => 'bg-amber-50'];
        }
        if ($reportFeatures['bar'] || $reportFeatures['lounge']) {
            $divisions[] = ['label' => 'Bar & lounge', 'value' => $summary['bar'], 'color' => 'text-sky-700', 'bg' => 'bg-sky-50'];
        }
        if ($reportFeatures['hotel']) {
            $divisions[] = ['label' => 'Ancillary', 'value' => $summary['ancillary'], 'color' => 'text-violet-700', 'bg' => 'bg-violet-50'];
        }
        $maxValue = max(1, (float) $summary['total']);
        $divisionCols = max(1, count($divisions));
    @endphp

    <form method="GET" class="mb-6 space-y-4">
        <div class="flex flex-wrap gap-1.5">
            @foreach ($periods as $key => $label)
                <a href="{{ route('tenant.reports.sales-summary', ['period' => $key, 'date' => $date->format('Y-m-d')]) }}"
                   @class([
                       'min-h-8 rounded-full border px-3 py-1.5 text-xs font-medium transition',
                       'border-primary bg-primary/10 text-primary' => $period === $key,
                       'border-slate-200 text-ink-muted hover:border-primary/40 hover:text-primary' => $period !== $key,
                   ])>
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="flex min-w-0 flex-1 basis-full flex-col gap-1 sm:flex-none sm:basis-auto">
                <label class="text-xs font-medium text-ink-muted">
                    @if ($period === 'monthly')
                        Month
                    @elseif ($period === 'weekly')
                        Week containing
                    @else
                        Date
                    @endif
                </label>
                <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="input-field w-full min-w-0 sm:w-auto" onchange="this.form.submit()">
            </div>
            <input type="hidden" name="period" value="{{ $period }}">

            <div class="inline-flex w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800 sm:w-auto">
                <a href="{{ route('tenant.reports.sales-summary.export-excel', $ep) }}"
                   class="flex-1 px-3 py-2.5 text-center text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20 sm:flex-none">Excel</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.sales-summary.export-pdf', $ep) }}"
                   class="flex-1 px-3 py-2.5 text-center text-sm font-medium text-ink transition hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20 sm:flex-none">PDF</a>
            </div>

            @if (\App\Support\TenantFeatures::allowsReportsHub())
                <a href="{{ route('tenant.reports') }}" class="btn-outline w-full sm:w-auto">All reports</a>
            @endif
        </div>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.kpi-card label="Total posted sales" :value="$currency.' '.$fmt($summary['total'])" accent="green" />
        <x-ui.kpi-card label="Payments collected" :value="$currency.' '.$fmt($summary['payments_collected'])" accent="blue" />
        @if ($reportFeatures['hotel'])
            <x-ui.kpi-card label="Room nights occupied" :value="(string) $summary['room_nights']"
                :subtitle="$report['period_label']" accent="sky" />
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-6">
            <h3 class="font-semibold text-ink">Division breakdown</h3>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $report['period_label'] }} · {{ $report['from'] }} — {{ $report['to'] }}</p>
        </div>

        <div @class([
            'grid divide-y divide-slate-100 sm:divide-x sm:divide-y-0',
            'sm:grid-cols-4' => $divisionCols >= 4,
            'sm:grid-cols-3' => $divisionCols === 3,
            'sm:grid-cols-2' => $divisionCols === 2,
            'sm:grid-cols-1' => $divisionCols <= 1,
        ])>
            @foreach ($divisions as $div)
                @php $pct = min(100, round($div['value'] / $maxValue * 100)); @endphp
                <div class="px-4 py-4 sm:px-5">
                    <p class="mb-1 text-xs font-medium text-ink-muted">{{ $div['label'] }}</p>
                    <p class="break-words text-base font-bold {{ $div['color'] }}">{{ $currency }} {{ $fmt($div['value']) }}</p>
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
            <div class="border-b border-slate-100 px-4 py-4 sm:px-6">
                <h3 class="font-semibold text-ink">Daily breakdown</h3>
                <p class="mt-0.5 text-xs text-ink-muted">Posted sales per day in this period</p>
            </div>

            <div class="table-scroll">
                <table class="w-full min-w-[40rem] text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <tr>
                            <th class="px-3 py-3 text-left sm:px-6">Date</th>
                            <th class="px-3 py-3 text-right sm:px-6">Rooms</th>
                            <th class="px-3 py-3 text-right sm:px-6">Restaurant</th>
                            <th class="px-3 py-3 text-right sm:px-6">Bar</th>
                            <th class="px-3 py-3 text-right sm:px-6">Ancillary</th>
                            <th class="px-3 py-3 text-right sm:px-6">Total</th>
                            <th class="px-3 py-3 text-right sm:px-6">Payments</th>
                            <th class="px-3 py-3 text-right sm:px-6">Room nights</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($report['daily_rows'] as $row)
                            <tr class="hover:bg-slate-50/60">
                                <td class="whitespace-nowrap px-3 py-3 font-medium text-ink sm:px-6">{{ $row['date_label'] }}</td>
                                <td class="px-3 py-3 text-right text-indigo-700 sm:px-6">{{ $fmt($row['rooms']) }}</td>
                                <td class="px-3 py-3 text-right text-amber-700 sm:px-6">{{ $fmt($row['restaurant']) }}</td>
                                <td class="px-3 py-3 text-right text-sky-700 sm:px-6">{{ $fmt($row['bar']) }}</td>
                                <td class="px-3 py-3 text-right text-violet-700 sm:px-6">{{ $fmt($row['ancillary']) }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-ink sm:px-6">{{ $fmt($row['total']) }}</td>
                                <td class="px-3 py-3 text-right text-emerald-700 sm:px-6">{{ $fmt($row['payments_collected']) }}</td>
                                <td class="px-3 py-3 text-right text-ink-muted sm:px-6">{{ $row['room_nights'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50 text-xs font-semibold">
                        <tr>
                            <td class="px-3 py-3 font-bold text-ink sm:px-6">Period total</td>
                            <td class="px-3 py-3 text-right text-indigo-700 sm:px-6">{{ $fmt($summary['rooms']) }}</td>
                            <td class="px-3 py-3 text-right text-amber-700 sm:px-6">{{ $fmt($summary['restaurant']) }}</td>
                            <td class="px-3 py-3 text-right text-sky-700 sm:px-6">{{ $fmt($summary['bar']) }}</td>
                            <td class="px-3 py-3 text-right text-violet-700 sm:px-6">{{ $fmt($summary['ancillary']) }}</td>
                            <td class="px-3 py-3 text-right font-bold text-ink sm:px-6">{{ $fmt($summary['total']) }}</td>
                            <td class="px-3 py-3 text-right font-bold text-emerald-700 sm:px-6">{{ $fmt($summary['payments_collected']) }}</td>
                            <td class="px-3 py-3 text-right font-bold text-ink sm:px-6">{{ $summary['room_nights'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

</x-layouts.app>
