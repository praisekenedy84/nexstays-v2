<x-layouts.app active-nav="reports" title="Reports" subtitle="Operations & finance reporting">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @can('view-fb-reports')
            <a href="{{ route('tenant.reports.fb-revenue') }}" class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                <h2 class="font-bold text-ink">F&B revenue split</h2>
                <p class="mt-1 text-sm text-ink-muted">Food vs drinks from folio charges</p>
            </a>
        @endcan
        @can('view-debts')
            <a href="{{ route('tenant.debts.index') }}" class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                <h2 class="font-bold text-ink">Outstanding debts</h2>
                <p class="mt-1 text-sm text-ink-muted">Open folios with balance due</p>
            </a>
        @endcan
        @can('view-reservations')
            <a href="{{ route('tenant.booked-list.index') }}" class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                <h2 class="font-bold text-ink">Booked list</h2>
                <p class="mt-1 text-sm text-ink-muted">Upcoming arrivals</p>
            </a>
        @endcan
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="card p-6">
            <h2 class="font-bold text-ink">HBMS & operations</h2>
            <ul class="mt-4 space-y-2 text-sm text-ink-muted">
                <li>· Occupancy & reservation lists</li>
                <li>· Till session history & over/short</li>
                <li>· Inventory low-stock alerts</li>
                <li>· Night audit — <code class="text-xs">nexstay:night-audit</code></li>
            </ul>
        </section>
        <section class="card p-6">
            <h2 class="font-bold text-ink">Finance modules</h2>
            <ul class="mt-4 space-y-2 text-sm text-ink-muted">
                <li>· Purchases (bar & kitchen stock)</li>
                <li>· Expenditures tracking</li>
                <li>· Ancillary / extra services</li>
                <li>· Room damage reports</li>
            </ul>
        </section>
    </div>
</x-layouts.app>
