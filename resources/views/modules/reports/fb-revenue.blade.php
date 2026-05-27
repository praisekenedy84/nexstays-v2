<x-layouts.app active-nav="fb-reports" title="F&B revenue split" subtitle="Food vs drinks revenue">
    <form method="GET">
        <x-ui.date-range-filter :from="$from->format('Y-m-d')" :to="$to->format('Y-m-d')">
            @php $ep = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]; @endphp
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('tenant.reports.fb-revenue.export', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-sky-50 hover:text-sky-700 dark:hover:bg-sky-900/20">CSV</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.fb-revenue.export-excel', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20">Excel</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.fb-revenue.export-pdf', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20">PDF</a>
            </div>
            <a href="{{ route('tenant.reports.fb-profitability', $ep) }}"
               class="btn-outline">Profitability &amp; top items →</a>
        </x-ui.date-range-filter>
    </form>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.kpi-card label="Food (restaurant)" :value="'TZS '.number_format((float) $revenue['food'])">
            <x-slot:icon><x-icon name="restaurant" class="size-6" /></x-slot:icon>
        </x-ui.kpi-card>
        <x-ui.kpi-card label="Drinks (bar + lounge)" :value="'TZS '.number_format((float) $revenue['drinks'])" accent="blue">
            <x-slot:icon><x-icon name="bar" class="size-6" /></x-slot:icon>
        </x-ui.kpi-card>
        <x-ui.kpi-card label="Total F&B" :value="'TZS '.number_format((float) $revenue['total'])" accent="sky">
            <x-slot:icon><x-icon name="chart" class="size-6" /></x-slot:icon>
        </x-ui.kpi-card>
    </div>
    <p class="mt-4 text-xs text-ink-subtle">Period: {{ $revenue['from'] }} — {{ $revenue['to'] }}. Drinks = bar + lounge folio transaction types.</p>
</x-layouts.app>
