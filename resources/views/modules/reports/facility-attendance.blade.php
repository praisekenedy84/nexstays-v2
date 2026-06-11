<x-layouts.app :active-nav="$navId" :title="$report['facility_label'] . ' report'" subtitle="Attendance and revenue collected">

    @php
        $currency = config('nexstay.currency.default', 'TZS');
        $fmt = fn (float $v) => number_format($v, 0);
        $exportBase = $facilityType === 'gym'
            ? 'tenant.reports.gym-attendance'
            : 'tenant.reports.pool-attendance';
        $ep = ['from' => $from, 'to' => $to];
        $summary = $report['summary'];
    @endphp

    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">From</label>
            <input type="date" name="from" value="{{ $from }}" class="input-field w-auto">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">To</label>
            <input type="date" name="to" value="{{ $to }}" class="input-field w-auto">
        </div>
        <button type="submit" class="btn-outline">Apply</button>

        <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <a href="{{ route($exportBase . '.export-excel', $ep) }}"
               class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700">Excel</a>
            <span class="w-px bg-slate-200"></span>
            <a href="{{ route($exportBase . '.export-pdf', $ep) }}"
               class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-rose-50 hover:text-rose-700">PDF</a>
        </div>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.kpi-card label="Total visits" :value="$summary['total_visits']" />
        <x-ui.kpi-card label="Walk-in" :value="$summary['walk_in_visits']" />
        <x-ui.kpi-card label="Hotel guests" :value="$summary['hotel_guest_visits']" />
        <x-ui.kpi-card label="Collected" :value="$fmt($summary['total_collected'])" :period="$currency" />
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="border-b px-5 py-3 text-sm font-semibold text-ink">Daily breakdown</div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                    <tr>
                        <th class="px-5 py-3 text-left">Date</th>
                        <th class="px-5 py-3 text-right">Visits</th>
                        <th class="px-5 py-3 text-right">Collected</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['daily_rows'] as $row)
                        <tr>
                            <td class="px-5 py-3">{{ $row['date_label'] }}</td>
                            <td class="px-5 py-3 text-right">{{ $row['visits'] }}</td>
                            <td class="px-5 py-3 text-right">{{ $fmt($row['collected']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-ink-muted">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b px-5 py-3 text-sm font-semibold text-ink">By settlement method</div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                    <tr>
                        <th class="px-5 py-3 text-left">Method</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($summary['by_settlement'] as $method => $amount)
                        @if ($amount > 0 || $method === 'complimentary')
                            <tr>
                                <td class="px-5 py-3 capitalize">{{ str_replace('_', ' ', $method) }}</td>
                                <td class="px-5 py-3 text-right">{{ $fmt($amount) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b px-5 py-3 text-sm font-semibold text-ink">Visit log</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3 text-left">Date</th>
                    <th class="px-5 py-3 text-left">Visitor</th>
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-left">Settlement</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                    <th class="px-5 py-3 text-left">Staff</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($report['attendances'] as $row)
                    <tr>
                        <td class="px-5 py-4 text-ink-muted">{{ $row->attended_at->format('d M Y H:i') }}</td>
                        <td class="px-5 py-4 font-medium">{{ $row->visitor_name }}</td>
                        <td class="px-5 py-4">{{ $row->reservation_id ? 'Hotel guest' : 'Walk-in' }}</td>
                        <td class="px-5 py-4 capitalize">{{ str_replace('_', ' ', $row->settlement) }}</td>
                        <td class="px-5 py-4 text-right">@money($row->amount)</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $row->recorder?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-ink-muted">No visits in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
