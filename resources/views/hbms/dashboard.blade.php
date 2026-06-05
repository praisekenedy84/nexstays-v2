<x-layouts.app active-nav="dashboard" title="Overview" subtitle="Front office snapshot for {{ $tenantLabel ?? tenant('id') }}">

@php
    $currency = config('nexstay.currency.default', 'TZS');
    $fmt      = fn (float $v) => number_format($v, 0);
@endphp

<div class="space-y-6">

    {{-- ===== ROW 1: OPERATIONS KPIs ===== --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.kpi-card label="Today arrivals" :value="(string) $todayArrivals" accent="orange">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h3a2.25 2.25 0 012.25 2.25v3.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card label="Today departures" :value="(string) $todayDepartures" accent="blue">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v3.75M3 12a9 9 0 1018 0 9 9 0 00-18 0z" />
                </svg>
            </x-slot:icon>
        </x-ui.kpi-card>

        <x-ui.kpi-card label="In-house & confirmed" :value="(string) $totalBooked" period="Active stays" accent="sky">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 4.5h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 011.875 1.875v9.75a1.875 1.875 0 01-1.875 1.875H5.625A1.875 1.875 0 013.75 16.125v-9.75A1.875 1.875 0 015.625 4.5z" />
                </svg>
            </x-slot:icon>
        </x-ui.kpi-card>
    </div>

    {{-- ===== ROW 2: TODAY'S REVENUE BY DIVISION ===== --}}
    @canany(['view-reports', 'view-fb-reports', 'view-reservations'])
    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h2 class="font-semibold text-ink">Today's posted sales</h2>
                <p class="text-xs text-ink-muted">Live charges &amp; POS · {{ now()->format('d M Y') }} · {{ $todaySales['room_nights'] }} room night{{ $todaySales['room_nights'] !== 1 ? 's' : '' }} occupied</p>
            </div>
            <span class="text-lg font-bold text-ink">
                {{ $currency }} {{ $fmt($todaySales['total']) }}
            </span>
        </div>

        <div class="grid divide-y divide-slate-100 sm:grid-cols-4 sm:divide-x sm:divide-y-0">
            @php
                $divisions = [
                    ['label' => 'Rooms (posted)', 'value' => $todaySales['rooms'],      'color' => 'text-indigo-700',  'bg' => 'bg-indigo-50'],
                    ['label' => 'Restaurant',     'value' => $todaySales['restaurant'], 'color' => 'text-amber-700',   'bg' => 'bg-amber-50'],
                    ['label' => 'Bar & lounge',   'value' => $todaySales['bar'],       'color' => 'text-sky-700',     'bg' => 'bg-sky-50'],
                    ['label' => 'Ancillary',      'value' => $todaySales['ancillary'],  'color' => 'text-violet-700',  'bg' => 'bg-violet-50'],
                ];
                $todayMax = max(1, $todaySales['total']);
            @endphp

            @foreach ($divisions as $div)
                @php $pct = min(100, round($div['value'] / $todayMax * 100)); @endphp
                <div class="px-5 py-4">
                    <p class="mb-1 text-xs font-medium text-ink-muted">{{ $div['label'] }}</p>
                    <p class="text-base font-bold {{ $div['color'] }}">{{ $fmt($div['value']) }}</p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full {{ $div['bg'] }} border border-current {{ $div['color'] }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Payments collected & confirmed arrivals --}}
        <div class="space-y-2 border-t border-slate-100 bg-slate-50/50 px-6 py-3 text-xs text-ink-muted">
            <p>
                Payments collected today:
                <span class="font-semibold text-emerald-700">{{ $currency }} {{ $fmt($todaySales['payments_collected']) }}</span>
            </p>
            @can('view-reservations')
                <p>
                    Confirmed arrivals today:
                    <span class="font-semibold text-indigo-700">{{ $currency }} {{ $fmt($todayBookedRooms['revenue']) }}</span>
                    <span class="text-ink-subtle">
                        · {{ $todayBookedRooms['reservation_count'] }} booking{{ $todayBookedRooms['reservation_count'] !== 1 ? 's' : '' }}
                        · {{ $todayBookedRooms['room_nights'] }} night{{ $todayBookedRooms['room_nights'] !== 1 ? 's' : '' }}
                    </span>
                </p>
            @endcan
        </div>
    </section>
    @endcanany

    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
        <div class="space-y-6">

            {{-- ===== MTD SUMMARY ===== --}}
            @canany(['view-reports', 'view-reservations'])
            <section class="card p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold text-ink">Month to date</h2>
                    <span class="text-xs text-ink-muted">{{ now()->format('M Y') }}</span>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-ink-muted">Posted sales</p>
                        <p class="text-xl font-bold text-ink">{{ $currency }} {{ $fmt($mtdSales['total']) }}</p>
                    </div>
                    @can('view-reservations')
                        <div>
                            <p class="text-xs text-ink-muted">Confirmed bookings revenue</p>
                            <p class="text-xl font-bold text-indigo-700">{{ $currency }} {{ $fmt($mtdBookedRooms['revenue']) }}</p>
                            <p class="mt-0.5 text-xs text-ink-subtle">
                                {{ $mtdBookedRooms['reservation_count'] }} reservation{{ $mtdBookedRooms['reservation_count'] !== 1 ? 's' : '' }}
                                · {{ $mtdBookedRooms['room_nights'] }} room nights
                            </p>
                        </div>
                    @endcan
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:col-span-2">
                        <div>
                            <p class="text-xs text-ink-muted">Rooms (posted)</p>
                            <p class="font-semibold text-indigo-700">{{ $fmt($mtdSales['rooms']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-muted">Restaurant</p>
                            <p class="font-semibold text-amber-700">{{ $fmt($mtdSales['restaurant']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-muted">Bar & lounge</p>
                            <p class="font-semibold text-sky-700">{{ $fmt($mtdSales['bar']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-muted">Ancillary</p>
                            <p class="font-semibold text-violet-700">{{ $fmt($mtdSales['ancillary']) }}</p>
                        </div>
                    </div>
                </div>

                {{-- 7-day trend from snapshots --}}
                @if ($recentSnapshots->isNotEmpty())
                    @php $snapMax = max(1, $recentSnapshots->max(fn ($s) => (float) $s->total)); @endphp
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="mb-3 text-xs font-medium text-ink-muted uppercase tracking-wide">
                            {{ $recentSnapshots->count() }}-day revenue trend
                        </p>
                        <div class="flex items-end gap-1.5 h-16">
                            @foreach ($recentSnapshots as $snap)
                                @php
                                    $barPct  = round((float) $snap->total / $snapMax * 100);
                                    $barH    = max(4, $barPct);
                                    $dayLbl  = \Carbon\Carbon::parse($snap->snapshot_date)->format('D');
                                @endphp
                                <div class="group relative flex flex-1 flex-col items-center justify-end gap-1"
                                     title="{{ $dayLbl }}: {{ $currency }} {{ number_format((float) $snap->total) }}">
                                    <div class="w-full rounded-t bg-primary/70 transition group-hover:bg-primary"
                                         style="height: {{ $barH }}%"></div>
                                    <span class="text-[10px] text-ink-muted">{{ $dayLbl }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-ink-subtle">
                            Snapshots written nightly by the night audit. Today's bar reflects live data once the audit runs.
                        </p>
                    </div>
                @else
                    <p class="mt-4 text-xs text-ink-subtle">
                        No trend data yet — snapshots are written nightly by the night audit.
                    </p>
                @endif
            </section>
            @endcanany

            {{-- ===== PRODUCT SALES INSIGHTS ===== --}}
            @canany(['view-reports', 'view-fb-reports'])
            @if ($topProducts->isNotEmpty())
            <section class="card p-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <h2 class="font-semibold text-ink">F&amp;B sales insights</h2>
                        <p class="text-xs text-ink-muted">{{ now()->format('M Y') }} · top items by quantity sold</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase tracking-wide text-ink-muted">Orders today</p>
                        <p class="text-xl font-bold text-ink">{{ $todayFbOrders }}</p>
                    </div>
                </div>

                {{-- Today's F&B split KPIs --}}
                <div class="mb-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-amber-50 px-4 py-3 dark:bg-amber-900/20">
                        <p class="text-[10px] font-medium uppercase tracking-wide text-amber-700 dark:text-amber-400">Restaurant today</p>
                        <p class="mt-0.5 text-base font-bold text-amber-700 dark:text-amber-400">{{ $currency }} {{ $fmt($todaySales['restaurant']) }}</p>
                    </div>
                    <div class="rounded-xl bg-sky-50 px-4 py-3 dark:bg-sky-900/20">
                        <p class="text-[10px] font-medium uppercase tracking-wide text-sky-700 dark:text-sky-400">Bar &amp; lounge today</p>
                        <p class="mt-0.5 text-base font-bold text-sky-700 dark:text-sky-400">{{ $currency }} {{ $fmt($todaySales['bar']) }}</p>
                    </div>
                </div>

                {{-- Top items horizontal bar chart --}}
                @php $maxQty = max(1, $topProducts->max('qty_sold')); @endphp
                <div class="space-y-2.5">
                    @foreach ($topProducts as $item)
                        @php $pct = max(3, round($item->qty_sold / $maxQty * 100)); @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <span class="min-w-0 flex-1 truncate text-xs font-medium text-ink">{{ $item->item_name }}</span>
                                <span class="shrink-0 text-xs text-ink-muted">{{ $item->qty_sold }}x &middot; {{ $currency }} {{ number_format((float) $item->revenue) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-amber-400 transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- 7-day F&B trend (restaurant + bar side-by-side) --}}
                @if ($recentSnapshots->isNotEmpty())
                    @php
                        $fbMax = max(1, $recentSnapshots->max(fn ($s) => (float) $s->restaurant + (float) $s->bar));
                        $barMaxPx = 48;
                    @endphp
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="mb-3 text-xs font-medium uppercase tracking-wide text-ink-muted">
                            {{ $recentSnapshots->count() }}-day F&amp;B trend
                        </p>
                        <div class="flex items-end gap-2" style="height: {{ $barMaxPx + 18 }}px">
                            @foreach ($recentSnapshots as $snap)
                                @php
                                    $rPx  = max(2, (int) round((float) $snap->restaurant / $fbMax * $barMaxPx));
                                    $bPx  = max(2, (int) round((float) $snap->bar / $fbMax * $barMaxPx));
                                    $dayLbl = \Carbon\Carbon::parse($snap->snapshot_date)->format('D');
                                @endphp
                                <div class="flex flex-1 flex-col items-center gap-1">
                                    <div class="flex w-full items-end justify-center gap-px"
                                         style="height: {{ $barMaxPx }}px"
                                         title="{{ $dayLbl }}: Restaurant {{ $currency }} {{ number_format((float) $snap->restaurant) }} · Bar {{ $currency }} {{ number_format((float) $snap->bar) }}">
                                        <div class="flex-1 rounded-t bg-amber-400/80 transition hover:bg-amber-400"
                                             style="height: {{ $rPx }}px"></div>
                                        <div class="flex-1 rounded-t bg-sky-400/80 transition hover:bg-sky-400"
                                             style="height: {{ $bPx }}px"></div>
                                    </div>
                                    <span class="text-[10px] text-ink-muted">{{ $dayLbl }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2 flex gap-4 text-[10px] text-ink-subtle">
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block size-2 rounded-full bg-amber-400"></span> Restaurant
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block size-2 rounded-full bg-sky-400"></span> Bar &amp; lounge
                            </span>
                        </div>
                    </div>
                @endif
            </section>
            @endif
            @endcanany

            {{-- ===== QUICK ACTIONS ===== --}}
            <section class="card p-6">
                <h2 class="font-semibold text-ink">Quick actions</h2>
                <div class="mt-4 flex flex-wrap gap-3">
                    @can('view-availability')
                        <a href="{{ route('tenant.availability') }}" class="btn-primary">Search availability</a>
                    @endcan
                    @can('view-reservations')
                        <a href="{{ route('tenant.reservations.index') }}" class="btn-outline">Reservations</a>
                        <a href="{{ route('tenant.booked-list.index') }}" class="btn-outline">Booked list</a>
                    @endcan
                    @can('view-rooms')
                        <a href="{{ route('tenant.rooms.index') }}" class="btn-outline">Room board</a>
                    @endcan
                    @can('view-guests')
                        <a href="{{ route('tenant.guests.index') }}" class="btn-outline">Guests</a>
                    @endcan
                    @can('view-orders')
                        <a href="{{ route('tenant.restaurant.index') }}" class="btn-outline">Restaurant</a>
                        <a href="{{ route('tenant.bar.index') }}" class="btn-outline">Bar</a>
                    @endcan
                    @can('view-till')
                        <a href="{{ route('tenant.till.index') }}" class="btn-outline">Till</a>
                    @endcan
                    @can('view-reports')
                        <a href="{{ route('tenant.reports') }}" class="btn-outline">Reports</a>
                    @endcan
                </div>
            </section>

        </div>

        {{-- ===== SIDEBAR ===== --}}
        <aside class="space-y-6">

            @if ($lastReservation)
                <section class="card p-5">
                    <h2 class="font-semibold text-ink">Latest reservation</h2>
                    <p class="mt-2 text-sm font-semibold text-primary">{{ $lastReservation->booking_ref }}</p>
                    <p class="text-ink">{{ $lastReservation->guest?->first_name }} {{ $lastReservation->guest?->last_name }}</p>
                    <p class="text-sm text-ink-muted">{{ $lastReservation->roomType?->name }}</p>
                    <p class="mt-1 text-xs text-ink-subtle">
                        {{ $lastReservation->check_in_date->format('d M') }} – {{ $lastReservation->check_out_date->format('d M') }}
                    </p>
                    <x-ui.status-badge :status="$lastReservation->status" class="mt-3" />
                </section>
            @endif

            <section class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-ink">Upcoming arrivals</h2>
                    @can('view-reservations')
                        <a href="{{ route('tenant.booked-list.index') }}" class="text-xs font-medium text-primary hover:underline">View all</a>
                    @endcan
                </div>
                <ul class="mt-4 divide-y divide-slate-100">
                    @forelse ($upcomingArrivals as $reservation)
                        <li>
                            <div class="flex items-center gap-3 py-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                                    {{ strtoupper(substr($reservation->guest?->first_name ?? '?', 0, 1)) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-ink">
                                        {{ $reservation->guest?->first_name }} {{ $reservation->guest?->last_name }}
                                    </span>
                                    <span class="block text-xs text-ink-muted">
                                        {{ $reservation->room?->room_number ? 'Rm '.$reservation->room->room_number.' · ' : '' }}
                                        {{ $reservation->check_in_date->format('d M') }} – {{ $reservation->check_out_date->format('d M') }}
                                    </span>
                                </span>
                                <x-ui.status-badge :status="$reservation->status" />
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-ink-muted">No upcoming arrivals</li>
                    @endforelse
                </ul>
            </section>

        </aside>
    </div>

</div>
</x-layouts.app>
