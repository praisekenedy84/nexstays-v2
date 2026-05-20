<x-layouts.app active-nav="fb-reports" title="F&B revenue split" subtitle="Food vs drinks from folio charges">
    <form method="GET" class="mb-6 flex flex-wrap gap-3">
        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="input-field w-auto">
        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="input-field w-auto">
        <button type="submit" class="btn-primary">Update</button>
        <a href="{{ route('tenant.reports') }}" class="btn-outline">All reports</a>
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
