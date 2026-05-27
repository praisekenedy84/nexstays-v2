<x-layouts.app active-nav="room-reservation-reports" title="Room reservations finance" subtitle="Finance and accounting view for room bookings">
    <form method="GET">
        <x-ui.date-range-filter :from="$from->format('Y-m-d')" :to="$to->format('Y-m-d')">
            @php $ep = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]; @endphp
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('tenant.reports.room-reservations.export', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-sky-50 hover:text-sky-700 dark:hover:bg-sky-900/20">CSV</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.room-reservations.export-excel', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20">Excel</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.room-reservations.export-pdf', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20">PDF</a>
            </div>
        </x-ui.date-range-filter>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card label="Total reservations" :value="number_format((int) $report['total_reservations'])">
            <x-slot:icon><x-icon name="calendar" class="size-6" /></x-slot:icon>
        </x-ui.kpi-card>
        <x-ui.kpi-card label="Room nights" :value="number_format((int) $report['room_nights'])" accent="blue">
            <x-slot:icon><x-icon name="moon" class="size-6" /></x-slot:icon>
        </x-ui.kpi-card>
        <x-ui.kpi-card label="Room revenue recognized" :value="'TZS '.number_format((float) $report['projected_room_revenue'])" accent="sky">
            <x-slot:icon><x-icon name="chart" class="size-6" /></x-slot:icon>
        </x-ui.kpi-card>
        <x-ui.kpi-card label="Payments collected (prepaid)" :value="'TZS '.number_format((float) $report['deposits_collected'])" accent="green">
            <x-slot:icon><x-icon name="wallet" class="size-6" /></x-slot:icon>
        </x-ui.kpi-card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="card p-6 lg:col-span-2">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Reservation status mix</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($report['status_counts'] as $status => $count)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-ink-muted">{{ str_replace('_', ' ', $status) }}</p>
                        <p class="mt-1 text-xl font-bold text-ink">{{ number_format((int) $count) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Accounting summary</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-ink-muted">Room revenue recognized</dt>
                    <dd class="font-semibold text-ink">TZS {{ number_format((float) $report['projected_room_revenue']) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-ink-muted">Payments collected (prepaid)</dt>
                    <dd class="font-semibold text-ink">TZS {{ number_format((float) $report['deposits_collected']) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-slate-100 pt-3">
                    <dt class="text-ink-muted">Balance expected on arrival</dt>
                    <dd class="font-bold text-primary">TZS {{ number_format((float) $report['balance_expected_on_arrival']) }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-ink-subtle">Inquiry reservations are excluded from recognized revenue and included in expected-on-arrival until confirmed.</p>
            <p class="mt-2 text-xs text-ink-subtle">Period: {{ $report['from'] }} — {{ $report['to'] }}</p>
        </section>
    </div>
</x-layouts.app>
