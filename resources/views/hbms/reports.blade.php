<x-layouts.app active-nav="reports" title="Reports Center" subtitle="Operational and financial insights in one place">
    <section class="card mb-6 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-subtle">NexStay Analytics</p>
                <h2 class="mt-1 text-xl font-bold text-ink">Choose a report category</h2>
                <p class="mt-2 max-w-2xl text-sm text-ink-muted">
                    Use these report links as your navigation hub for reservation performance, room revenue, debts, and F&B analytics.
                </p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-ink-muted">
                <p class="font-semibold text-ink">Tip</p>
                <p class="mt-1">Filter each report by date range for cleaner period analysis.</p>
            </div>
        </div>
    </section>

    @can('manage-reservations')
        <section class="card mb-6 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-subtle">Report Delivery</p>
                    <h3 class="mt-1 text-lg font-bold text-ink">Daily report email setup</h3>
                    <p class="mt-2 max-w-2xl text-sm text-ink-muted">
                        Set where and when daily report emails should be sent, then use send now for an instant copy.
                    </p>
                </div>
            </div>

            <form action="{{ route('tenant.reports.delivery-settings.update') }}" method="POST" class="mt-5 grid gap-4 md:grid-cols-3">
                @csrf
                @method('PUT')
                <label class="flex flex-col gap-1 text-sm text-ink-muted">
                    <span class="font-medium text-ink">Recipient email</span>
                    <input
                        type="email"
                        name="recipient_email"
                        value="{{ old('recipient_email', $deliverySettings['recipient_email'] ?? '') }}"
                        required
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-ink shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                    @error('recipient_email')
                        <span class="text-xs text-rose-600">{{ $message }}</span>
                    @enderror
                </label>
                <label class="flex flex-col gap-1 text-sm text-ink-muted">
                    <span class="font-medium text-ink">Daily send time</span>
                    <input
                        type="time"
                        name="send_time"
                        value="{{ old('send_time', $deliverySettings['send_time'] ?? '08:00') }}"
                        required
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-ink shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                    @error('send_time')
                        <span class="text-xs text-rose-600">{{ $message }}</span>
                    @enderror
                </label>
                <label class="flex flex-col gap-1 text-sm text-ink-muted">
                    <span class="font-medium text-ink">Timezone</span>
                    <select
                        name="timezone"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-ink shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        @foreach(\DateTimeZone::listIdentifiers() as $timezone)
                            <option
                                value="{{ $timezone }}"
                                @selected(old('timezone', $deliverySettings['timezone'] ?? config('app.timezone')) === $timezone)
                            >
                                {{ $timezone }}
                            </option>
                        @endforeach
                    </select>
                    @error('timezone')
                        <span class="text-xs text-rose-600">{{ $message }}</span>
                    @enderror
                </label>

                <div class="md:col-span-3 flex flex-wrap gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90"
                    >
                        Save report email settings
                    </button>
                </div>
            </form>

            <form action="{{ route('tenant.reports.delivery-settings.send-now') }}" method="POST" class="mt-3">
                @csrf
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg border border-primary/30 bg-white px-4 py-2 text-sm font-semibold text-primary transition hover:border-primary hover:bg-primary/5"
                >
                    Send report email now
                </button>
            </form>
        </section>
    @endcan

    <div class="mb-6 flex flex-wrap gap-2 text-xs">
        @can('view-reservations')
            <a href="#front-office-reports" class="rounded-full border border-slate-200 px-3 py-1.5 text-ink-muted transition hover:border-primary/30 hover:text-primary">Front Office</a>
        @endcan
        @canany(['view-reports', 'view-debts'])
            <a href="#finance-reports" class="rounded-full border border-slate-200 px-3 py-1.5 text-ink-muted transition hover:border-primary/30 hover:text-primary">Finance</a>
        @endcanany
        @can('view-fb-reports')
            <a href="#fb-reports" class="rounded-full border border-slate-200 px-3 py-1.5 text-ink-muted transition hover:border-primary/30 hover:text-primary">Food & Beverage</a>
        @endcan
    </div>

    @can('view-reservations')
        <section id="front-office-reports" class="mb-6">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Front Office Reports</h3>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('tenant.booked-list.index') }}" class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Arrivals</p>
                    <h4 class="mt-1 font-bold text-ink">Booked list</h4>
                    <p class="mt-1 text-sm text-ink-muted">Upcoming check-ins and reservation movement.</p>
                </a>
                @canany(['view-reports', 'view-reservations'])
                    <a href="{{ route('tenant.reports.room-reservations') }}" class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Revenue</p>
                        <h4 class="mt-1 font-bold text-ink">Room reservations finance</h4>
                        <p class="mt-1 text-sm text-ink-muted">Projected room revenue, deposits, and booking status mix.</p>
                    </a>
                    <a href="{{ route('tenant.reports.room-payments-accounting') }}" class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Accounting</p>
                        <h4 class="mt-1 font-bold text-ink">Room payments & accounting</h4>
                        <p class="mt-1 text-sm text-ink-muted">Guest-by-guest payment, room, stay nights, and outstanding balances.</p>
                    </a>
                @endcanany
            </div>
        </section>
    @endcan

    @canany(['view-reports', 'view-debts'])
        <section id="finance-reports" class="mb-6">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Finance Reports</h3>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @can('view-debts')
                    <a href="{{ route('tenant.debts.index') }}" class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Receivables</p>
                        <h4 class="mt-1 font-bold text-ink">Outstanding debts</h4>
                        <p class="mt-1 text-sm text-ink-muted">Open folios with current balance due.</p>
                    </a>
                @endcan
                <div class="card border-dashed p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-subtle">More reports</p>
                    <h4 class="mt-1 font-bold text-ink">Coming next</h4>
                    <p class="mt-1 text-sm text-ink-muted">Night audit summary, occupancy trend, and room-type performance.</p>
                </div>
            </div>
        </section>
    @endcanany

    @can('view-fb-reports')
        <section id="fb-reports">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Food & Beverage Reports</h3>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('tenant.reports.fb-revenue') }}" class="card block p-5 transition hover:ring-2 hover:ring-primary/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Sales mix</p>
                    <h4 class="mt-1 font-bold text-ink">F&B revenue split</h4>
                    <p class="mt-1 text-sm text-ink-muted">Compare restaurant food sales against bar and lounge drinks.</p>
                </a>
            </div>
        </section>
    @endcan
</x-layouts.app>
