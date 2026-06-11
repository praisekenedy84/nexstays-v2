<x-layouts.app active-nav="reports" title="Reports" subtitle="Operational and financial insights">

    @can('manage-reservations')
        <section class="card mb-6 p-6">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-ink-muted">Daily report email</h3>
            <form action="{{ route('tenant.reports.delivery-settings.update') }}" method="POST"
                  class="grid gap-4 md:grid-cols-3">
                @csrf
                @method('PUT')
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-ink">Recipient email</span>
                    <input type="email" name="recipient_email"
                           value="{{ old('recipient_email', $deliverySettings['recipient_email'] ?? '') }}"
                           required
                           class="input-field">
                    @error('recipient_email')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-ink">Daily send time</span>
                    <input type="time" name="send_time"
                           value="{{ old('send_time', $deliverySettings['send_time'] ?? '08:00') }}"
                           required class="input-field">
                    @error('send_time')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-ink">Timezone</span>
                    <select name="timezone" id="report-timezone" class="input-field">
                        @foreach(\DateTimeZone::listIdentifiers() as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $deliverySettings['timezone'] ?? app(\App\Domain\Shared\Services\TimezoneService::class)->resolve()) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                    <span class="text-xs text-ink-muted" id="report-timezone-hint"></span>
                </label>
                <div class="flex flex-wrap gap-3 md:col-span-3">
                    <button type="submit" class="btn-primary">Save email settings</button>
                </div>
            </form>
            <form action="{{ route('tenant.reports.delivery-settings.send-now') }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="btn-outline">Send report now</button>
            </form>
        </section>
    @endcan

    {{-- ===== SALES ===== --}}
    @canany(['view-reports', 'view-fb-reports', 'view-reservations'])
        <section class="mb-8">
            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">Sales</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                <a href="{{ route('tenant.reports.sales-summary') }}"
                   class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Summary</p>
                    <h4 class="mt-1 font-bold text-ink">Sales summary</h4>
                    <p class="mt-1 text-sm text-ink-muted">Daily, weekly, and monthly posted sales by division — PDF and Excel export.</p>
                </a>

                @can('view-fb-reports')
                    <a href="{{ route('tenant.reports.bar-sales-summary') }}"
                       class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Bar</p>
                        <h4 class="mt-1 font-bold text-ink">Bar item sales summary</h4>
                        <p class="mt-1 text-sm text-ink-muted">Quantity, price, tax, and totals by bar menu item — PDF and Excel export.</p>
                    </a>

                    <a href="{{ route('tenant.reports.lounge-sales-summary') }}"
                       class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Lounge</p>
                        <h4 class="mt-1 font-bold text-ink">Lounge item sales summary</h4>
                        <p class="mt-1 text-sm text-ink-muted">Quantity, price, tax, and totals by lounge menu item — PDF and Excel export.</p>
                    </a>

                    <a href="{{ route('tenant.reports.menu-item-sales-summary') }}"
                       class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Item detail</p>
                        <h4 class="mt-1 font-bold text-ink">Menu item sales summary</h4>
                        <p class="mt-1 text-sm text-ink-muted">Quantity, price, tax, and totals by menu item — PDF and Excel export.</p>
                    </a>
                @endcan

            </div>
        </section>
    @endcanany

    {{-- ===== FRONT OFFICE ===== --}}
    @canany(['view-reservations'])
        <section class="mb-8">
            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">Front office</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                <a href="{{ route('tenant.booked-list.index') }}"
                   class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Arrivals</p>
                    <h4 class="mt-1 font-bold text-ink">Booked list</h4>
                    <p class="mt-1 text-sm text-ink-muted">Upcoming check-ins and reservation movement.</p>
                </a>

                <a href="{{ route('tenant.reports.occupancy') }}"
                   class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Occupancy</p>
                    <h4 class="mt-1 font-bold text-ink">Occupancy report</h4>
                    <p class="mt-1 text-sm text-ink-muted">Daily occupancy %, ADR, and RevPAR with CSV export.</p>
                </a>

                <a href="{{ route('tenant.reports.room-reservations') }}"
                   class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Revenue</p>
                    <h4 class="mt-1 font-bold text-ink">Room reservations finance</h4>
                    <p class="mt-1 text-sm text-ink-muted">Projected room revenue, deposits, and booking status mix.</p>
                </a>

                <a href="{{ route('tenant.reports.room-reservations-summary') }}"
                   class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Detail</p>
                    <h4 class="mt-1 font-bold text-ink">Room reservations summary</h4>
                    <p class="mt-1 text-sm text-ink-muted">Guest-by-guest stays grouped by room type and status — PDF and Excel export.</p>
                </a>

                <a href="{{ route('tenant.reports.room-payments-accounting') }}"
                   class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Accounting</p>
                    <h4 class="mt-1 font-bold text-ink">Room payments & accounting</h4>
                    <p class="mt-1 text-sm text-ink-muted">Guest-by-guest payment, stay nights, and outstanding balances.</p>
                </a>

            </div>
        </section>
    @endcanany

    {{-- ===== FINANCE ===== --}}
    @canany(['view-reports', 'view-debts'])
        <section class="mb-8">
            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">Finance</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                @can('view-debts')
                    <a href="{{ route('tenant.debts.index') }}"
                       class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Receivables</p>
                        <h4 class="mt-1 font-bold text-ink">Outstanding debts</h4>
                        <p class="mt-1 text-sm text-ink-muted">Open folios with current balance due.</p>
                    </a>
                @endcan

                @can('view-reports')
                    <a href="{{ route('tenant.reports.payment-summary') }}"
                       class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Collections</p>
                        <h4 class="mt-1 font-bold text-ink">Payment collection summary</h4>
                        <p class="mt-1 text-sm text-ink-muted">Totals by method — cash, mobile money, card, and folio.</p>
                    </a>
                @endcan

            </div>
        </section>
    @endcanany

    {{-- ===== FACILITIES ===== --}}
    @can('view-facility-reports')
        <section class="mb-8">
            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">Facilities</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                <a href="{{ route('tenant.reports.pool-attendance') }}"
                   class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Swimming Pool</p>
                    <h4 class="mt-1 font-bold text-ink">Pool attendance & revenue</h4>
                    <p class="mt-1 text-sm text-ink-muted">Visits, walk-ins, hotel guests, and money collected — PDF and Excel export.</p>
                </a>

                <a href="{{ route('tenant.reports.gym-attendance') }}"
                   class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Gym</p>
                    <h4 class="mt-1 font-bold text-ink">Gym attendance & revenue</h4>
                    <p class="mt-1 text-sm text-ink-muted">Member visits, settlement breakdown, and daily totals — PDF and Excel export.</p>
                </a>

            </div>
        </section>
    @endcan

    {{-- ===== FOOD & BEVERAGE ===== --}}
    @can('view-fb-reports')
        <section class="mb-8">
            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">Food & beverage</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                <a href="{{ route('tenant.reports.fb-revenue') }}"
                   class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Sales mix</p>
                    <h4 class="mt-1 font-bold text-ink">F&B revenue split</h4>
                    <p class="mt-1 text-sm text-ink-muted">Restaurant food vs bar and lounge drinks.</p>
                </a>

                <a href="{{ route('tenant.reports.fb-profitability') }}"
                   class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Profitability</p>
                    <h4 class="mt-1 font-bold text-ink">F&B profitability & top items</h4>
                    <p class="mt-1 text-sm text-ink-muted">Revenue vs cost, gross margin, stock purchases, top 15 items.</p>
                </a>

            </div>
        </section>
    @endcan

    @push('scripts')
        <script>
            (function () {
                const select = document.getElementById('report-timezone');
                const hint = document.getElementById('report-timezone-hint');
                if (!select || !hint) return;

                const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                if (!browserTimezone) return;

                hint.textContent = 'Detected on this device: ' + browserTimezone;

                const hasOption = Array.from(select.options).some((option) => option.value === browserTimezone);
                if (!hasOption) return;

                if (!select.value || select.value === '{{ app(\App\Domain\Shared\Services\TimezoneService::class)->fallback() }}') {
                    select.value = browserTimezone;
                }
            })();
        </script>
    @endpush
</x-layouts.app>
