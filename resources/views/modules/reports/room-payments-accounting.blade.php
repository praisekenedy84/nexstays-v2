<x-layouts.app active-nav="room-payments-accounting-reports" title="Room payments & accounting" subtitle="Guest-level reservation payment breakdown">
    <form method="GET">
        <x-ui.date-range-filter :from="$from->format('Y-m-d')" :to="$to->format('Y-m-d')">
            @php $ep = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]; @endphp
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('tenant.reports.room-payments-accounting.export', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-sky-50 hover:text-sky-700 dark:hover:bg-sky-900/20">CSV</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.room-payments-accounting.export-excel', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20">Excel</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.room-payments-accounting.export-pdf', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20">PDF</a>
            </div>
        </x-ui.date-range-filter>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.kpi-card label="Reservations" :value="number_format((int) $report['totals']['reservations'])" />
        <x-ui.kpi-card label="Room nights" :value="number_format((int) $report['totals']['room_nights'])" accent="blue" />
        <x-ui.kpi-card label="Room revenue" :value="'TZS '.number_format((float) $report['totals']['room_revenue'])" accent="sky" />
        <x-ui.kpi-card label="Payments received" :value="'TZS '.number_format((float) $report['totals']['payments_received'])" accent="green" />
        <x-ui.kpi-card label="Outstanding" :value="'TZS '.number_format((float) $report['totals']['outstanding_balance'])" accent="rose" />
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                    <tr>
                        <th class="px-4 py-3 text-left">Booking</th>
                        <th class="px-4 py-3 text-left">Guest</th>
                        <th class="px-4 py-3 text-left">Room</th>
                        <th class="px-4 py-3 text-left">Stay</th>
                        <th class="px-4 py-3 text-right">Room revenue</th>
                        <th class="px-4 py-3 text-right">Folio charges</th>
                        <th class="px-4 py-3 text-right">Payments</th>
                        <th class="px-4 py-3 text-right">Outstanding</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('tenant.reservations.show', $row['reservation']) }}" class="font-mono text-xs text-primary hover:underline">
                                    {{ $row['reservation']->booking_ref }}
                                </a>
                                <p class="mt-1 text-xs text-ink-subtle">{{ str_replace('_', ' ', $row['reservation']->status) }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $row['guest_name'] }}</td>
                            <td class="px-4 py-3">{{ $row['room_number'] }}</td>
                            <td class="px-4 py-3 text-ink-muted">
                                {{ $row['stay_nights'] }} night(s)
                                <p class="text-xs">
                                    {{ $row['reservation']->check_in_date?->format('d M Y') }} - {{ $row['reservation']->check_out_date?->format('d M Y') }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">TZS {{ number_format((float) $row['room_revenue']) }}</td>
                            <td class="px-4 py-3 text-right text-ink-muted">TZS {{ number_format((float) $row['folio_charges']) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700">TZS {{ number_format((float) $row['payments_received']) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-rose-700">TZS {{ number_format((float) $row['outstanding_balance']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-ink-muted">No reservation accounting data for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
