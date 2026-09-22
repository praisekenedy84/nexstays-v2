<x-layouts.app active-nav="dashboard" title="Overview" subtitle="{{ $showHotelSummary && $showFbSummary ? 'Hotel & F&B snapshot' : ($showFbSummary ? 'Restaurant / F&B snapshot' : 'Front office snapshot') }} for {{ $tenantLabel ?? tenant('id') }}">

@php
    $currency = config('nexstay.currency.default', 'TZS');
    $fmt      = fn (float $v) => number_format($v, 0);
    $trendPresets = [
        'today'         => 'Today',
        'yesterday'     => 'Yesterday',
        'this_month'    => 'This month',
        'last_30_days'  => 'Last 30 days',
    ];
    $fbScope = $fbScope ?? 'both';
    $fbScopeLabels = [
        'both'       => 'All F&B',
        'restaurant' => 'Restaurant',
        'bar'        => 'Bar & lounge',
    ];
    $fbScopeLabel = $fbScopeLabels[$fbScope] ?? 'All F&B';
@endphp

<div class="space-y-8">

    @if ($showHotelSummary)
    {{-- ===== HOTEL SUMMARY ===== --}}
    <div class="space-y-6">
        <div class="flex items-baseline justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-ink">Hotel summary</h2>
                <p class="text-xs text-ink-muted">Front office operations and room revenue</p>
            </div>
        </div>

        {{-- Ops KPIs --}}
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

        {{-- Hotel revenue trend --}}
        @canany(['view-reports', 'view-fb-reports', 'view-reservations'])
        <section class="card overflow-hidden">
            <form method="GET" action="{{ route('tenant.dashboard') }}">
                @if ($showFbSummary && $fbScope !== 'both')
                    <input type="hidden" name="fb" value="{{ $fbScope }}">
                @endif
                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-slate-100 px-4 py-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-ink">Hotel revenue</h3>
                        <p class="text-xs text-ink-muted">
                            @if ($trendIsHourly)
                                Hourly posted sales · {{ $trendFrom->format('d M Y') }}
                            @else
                                Daily posted sales (all divisions) · {{ $trendFrom->format('d M Y') }} – {{ $trendTo->format('d M Y') }}
                            @endif
                            @if ($trendTo->isToday() && ! $trendIsHourly)
                                · includes live data for today
                            @endif
                        </p>
                    </div>
                    @if (count($revenueTrend) > 0)
                        <div class="shrink-0 text-right">
                            <p class="text-[10px] uppercase tracking-wide text-ink-muted">Total sales</p>
                            <p class="break-words text-sm font-bold text-ink sm:text-base">{{ $currency }} {{ $fmt($hotelTrendTotal) }}</p>
                        </div>
                    @endif
                </div>

                <div class="border-b border-slate-100 px-4 py-2">
                    <x-ui.date-range-filter
                        :from="$trendFrom->format('Y-m-d')"
                        :to="$trendTo->format('Y-m-d')"
                        :today="now()->format('Y-m-d')"
                        :showReportsLink="false"
                        :presets="$trendPresets"
                        compact
                    />
                </div>

                <div class="px-3 py-3 sm:px-4">
                    <x-ui.line-chart
                        :labels="array_column($revenueTrend, 'label')"
                        :values="$hotelTrendValues"
                        :currency="$currency"
                        :height="150"
                        :labelInterval="$trendIsHourly ? 3 : null"
                        compact
                    >
                        No revenue data for this period.
                    </x-ui.line-chart>
                </div>
            </form>
        </section>
        @endcanany

        {{-- Today's posted sales (hotel-focused when F&B has its own summary) --}}
        @canany(['view-reports', 'view-fb-reports', 'view-reservations'])
        <section class="card overflow-hidden">
            <div class="card-money-header">
                <div class="min-w-0">
                    <h3 class="font-semibold text-ink">Today's posted sales</h3>
                    <p class="text-xs text-ink-muted">
                        Live charges &amp; POS · {{ now()->format('d M Y') }}
                        @if ($showHotelSummary)
                            · {{ $todaySales['room_nights'] }} room night{{ $todaySales['room_nights'] !== 1 ? 's' : '' }} occupied
                        @endif
                    </p>
                </div>
                <span class="money-total">
                    @if ($showFbSummary)
                        {{ $currency }} {{ $fmt($todaySales['rooms'] + $todaySales['ancillary']) }}
                    @else
                        {{ $currency }} {{ $fmt($todaySales['total']) }}
                    @endif
                </span>
            </div>

            <div class="grid divide-y divide-slate-100 {{ $showFbSummary ? 'sm:grid-cols-2' : 'sm:grid-cols-4' }} sm:divide-x sm:divide-y-0">
                @php
                    $divisions = $showFbSummary
                        ? [
                            ['label' => 'Rooms (posted)', 'value' => $todaySales['rooms'],     'color' => 'text-indigo-700', 'bg' => 'bg-indigo-50'],
                            ['label' => 'Ancillary',      'value' => $todaySales['ancillary'], 'color' => 'text-violet-700', 'bg' => 'bg-violet-50'],
                        ]
                        : [
                            ['label' => 'Rooms (posted)', 'value' => $todaySales['rooms'],      'color' => 'text-indigo-700',  'bg' => 'bg-indigo-50'],
                            ['label' => 'Restaurant',     'value' => $todaySales['restaurant'], 'color' => 'text-amber-700',   'bg' => 'bg-amber-50'],
                            ['label' => 'Bar & lounge',   'value' => $todaySales['bar'],        'color' => 'text-sky-700',     'bg' => 'bg-sky-50'],
                            ['label' => 'Ancillary',      'value' => $todaySales['ancillary'],  'color' => 'text-violet-700',  'bg' => 'bg-violet-50'],
                        ];
                    $todayMax = max(1, $showFbSummary
                        ? ($todaySales['rooms'] + $todaySales['ancillary'])
                        : $todaySales['total']);
                @endphp

                @foreach ($divisions as $div)
                    @php $pct = min(100, round($div['value'] / $todayMax * 100)); @endphp
                    <div class="px-4 py-4 sm:px-5">
                        <p class="mb-1 text-xs font-medium text-ink-muted">{{ $div['label'] }}</p>
                        <p class="break-words text-base font-bold {{ $div['color'] }}">{{ $fmt($div['value']) }}</p>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $div['bg'] }} border border-current {{ $div['color'] }}"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="space-y-2 border-t border-slate-100 bg-slate-50/50 px-4 py-3 text-xs text-ink-muted sm:px-6">
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
                {{-- MTD SUMMARY --}}
                @canany(['view-reports', 'view-reservations'])
                <section class="card card-pad">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold text-ink">Month to date</h3>
                        <span class="text-xs text-ink-muted">{{ now()->format('M Y') }}</span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="min-w-0">
                            <p class="text-xs text-ink-muted">Posted sales</p>
                            <p class="break-words text-lg font-bold text-ink sm:text-xl">
                                @if ($showFbSummary)
                                    {{ $currency }} {{ $fmt($mtdSales['rooms'] + $mtdSales['ancillary']) }}
                                @else
                                    {{ $currency }} {{ $fmt($mtdSales['total']) }}
                                @endif
                            </p>
                        </div>
                        @can('view-reservations')
                            <div class="min-w-0">
                                <p class="text-xs text-ink-muted">Confirmed bookings revenue</p>
                                <p class="break-words text-lg font-bold text-indigo-700 sm:text-xl">{{ $currency }} {{ $fmt($mtdBookedRooms['revenue']) }}</p>
                                <p class="mt-0.5 text-xs text-ink-subtle">
                                    {{ $mtdBookedRooms['reservation_count'] }} reservation{{ $mtdBookedRooms['reservation_count'] !== 1 ? 's' : '' }}
                                    · {{ $mtdBookedRooms['room_nights'] }} room nights
                                </p>
                            </div>
                        @endcan
                        <div class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm sm:col-span-2 sm:gap-x-4">
                            <div class="min-w-0">
                                <p class="text-xs text-ink-muted">Rooms (posted)</p>
                                <p class="break-words font-semibold text-indigo-700">{{ $fmt($mtdSales['rooms']) }}</p>
                            </div>
                            @unless ($showFbSummary)
                                <div class="min-w-0">
                                    <p class="text-xs text-ink-muted">Restaurant</p>
                                    <p class="break-words font-semibold text-amber-700">{{ $fmt($mtdSales['restaurant']) }}</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-ink-muted">Bar & lounge</p>
                                    <p class="break-words font-semibold text-sky-700">{{ $fmt($mtdSales['bar']) }}</p>
                                </div>
                            @endunless
                            <div class="min-w-0">
                                <p class="text-xs text-ink-muted">Ancillary</p>
                                <p class="break-words font-semibold text-violet-700">{{ $fmt($mtdSales['ancillary']) }}</p>
                            </div>
                        </div>
                    </div>

                    @if ($recentSnapshots->isNotEmpty())
                        <p class="mt-4 border-t border-slate-100 pt-4 text-xs text-ink-subtle">
                            See the hotel revenue chart above for the daily trend.
                        </p>
                    @else
                        <p class="mt-4 text-xs text-ink-subtle">
                            No trend data yet — snapshots are written nightly by the night audit.
                        </p>
                    @endif
                </section>
                @endcanany
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-6">
                @if ($lastReservation)
                    <section class="card p-4 sm:p-5">
                        <h3 class="font-semibold text-ink">Latest reservation</h3>
                        <p class="mt-2 text-sm font-semibold text-primary">{{ $lastReservation->booking_ref }}</p>
                        <p class="text-ink">{{ $lastReservation->guest?->first_name }} {{ $lastReservation->guest?->last_name }}</p>
                        <p class="text-sm text-ink-muted">{{ $lastReservation->roomType?->name }}</p>
                        <p class="mt-1 text-xs text-ink-subtle">
                            {{ $lastReservation->check_in_date->format('d M') }} – {{ $lastReservation->check_out_date->format('d M') }}
                        </p>
                        <x-ui.status-badge :status="$lastReservation->status" class="mt-3" />
                    </section>
                @endif

                <section class="card p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold text-ink">Upcoming arrivals</h3>
                        @can('view-reservations')
                            <a href="{{ route('tenant.booked-list.index') }}" class="text-xs font-medium text-primary hover:underline">View all</a>
                        @endcan
                    </div>
                    <ul class="mt-4 divide-y divide-slate-100">
                        @forelse ($upcomingArrivals as $reservation)
                            <li>
                                <div class="flex items-start gap-3 py-3 sm:items-center">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                                        {{ strtoupper(substr($reservation->guest?->first_name ?? '?', 0, 1)) }}
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-ink">
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
    @endif

    @if ($showFbSummary)
    {{-- ===== RESTAURANT / F&B SUMMARY ===== --}}
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-ink">Restaurant / F&amp;B summary</h2>
                <p class="text-xs text-ink-muted">Outlet sales, orders, and menu performance</p>
            </div>
            <div class="segmented-control w-full sm:w-auto" role="group" aria-label="F&amp;B sales filter">
                @foreach ($fbScopeLabels as $scopeKey => $scopeLabel)
                    @php
                        $scopeQuery = array_filter([
                            'from' => $trendFrom->format('Y-m-d'),
                            'to'   => $trendTo->format('Y-m-d'),
                            'fb'   => $scopeKey !== 'both' ? $scopeKey : null,
                        ]);
                        $isActive = $fbScope === $scopeKey;
                    @endphp
                    <a
                        href="{{ route('tenant.dashboard', $scopeQuery) }}"
                        @class([
                            'bg-white text-ink shadow-sm dark:bg-slate-700 dark:text-ink' => $isActive,
                            'text-ink-muted hover:text-ink' => ! $isActive,
                        ])
                    >{{ $scopeLabel }}</a>
                @endforeach
            </div>
        </div>

        {{-- F&B KPIs --}}
        @canany(['view-reports', 'view-fb-reports', 'view-orders'])
        <div @class([
            'grid gap-4',
            'sm:grid-cols-3' => $fbScope === 'both',
            'sm:grid-cols-2' => $fbScope !== 'both',
        ])>
            <x-ui.kpi-card label="Orders today" :value="(string) $todayFbOrders" accent="orange">
                <x-slot:icon>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </x-slot:icon>
            </x-ui.kpi-card>

            @if ($fbScope !== 'bar')
            <x-ui.kpi-card label="Restaurant today" :value="$currency.' '.$fmt($todaySales['restaurant'])" accent="orange">
                <x-slot:icon>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75l-1.5.75a3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0L3 16.5m15-3.379a48.474 48.474 0 00-6-.371c-2.032 0-4.034.126-6 .371m12 0c.39.049.777.102 1.163.16 1.07.16 1.837 1.094 1.837 2.175v5.17c0 .62-.504 1.124-1.125 1.124H4.125A1.125 1.125 0 013 20.625v-5.17c0-1.08.768-2.014 1.837-2.174A47.78 47.78 0 016 13.12" />
                    </svg>
                </x-slot:icon>
            </x-ui.kpi-card>
            @endif

            @if ($fbScope !== 'restaurant')
            <x-ui.kpi-card label="Bar & lounge today" :value="$currency.' '.$fmt($todaySales['bar'])" accent="sky">
                <x-slot:icon>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.663 0-5.268-.226-7.805-.661-1.717-.293-2.3-2.379-1.067-3.611L5 14.5" />
                    </svg>
                </x-slot:icon>
            </x-ui.kpi-card>
            @endif
        </div>
        @endcanany

        {{-- F&B revenue trend --}}
        @canany(['view-reports', 'view-fb-reports'])
        <section class="card overflow-hidden">
            @if (! $showHotelSummary)<form method="GET" action="{{ route('tenant.dashboard') }}">@endif
                @if (! $showHotelSummary && $fbScope !== 'both')
                    <input type="hidden" name="fb" value="{{ $fbScope }}">
                @endif
                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-slate-100 px-4 py-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-ink">{{ $fbScopeLabel }} revenue</h3>
                        <p class="text-xs text-ink-muted">
                            @if ($trendIsHourly)
                                Hourly {{ strtolower($fbScopeLabel) }} sales · {{ $trendFrom->format('d M Y') }}
                            @else
                                Daily {{ strtolower($fbScopeLabel) }} sales · {{ $trendFrom->format('d M Y') }} – {{ $trendTo->format('d M Y') }}
                            @endif
                            @if ($trendTo->isToday() && ! $trendIsHourly)
                                · includes live data for today
                            @endif
                        </p>
                    </div>
                    @if (count($fbRevenueTrend) > 0)
                        <div class="shrink-0 text-right">
                            <p class="text-[10px] uppercase tracking-wide text-ink-muted">Total sales</p>
                            <p class="break-words text-sm font-bold text-ink sm:text-base">{{ $currency }} {{ $fmt($fbTrendTotal) }}</p>
                        </div>
                    @endif
                </div>

                @unless ($showHotelSummary)
                <div class="border-b border-slate-100 px-4 py-2">
                    <x-ui.date-range-filter
                        :from="$trendFrom->format('Y-m-d')"
                        :to="$trendTo->format('Y-m-d')"
                        :today="now()->format('Y-m-d')"
                        :showReportsLink="false"
                        :presets="$trendPresets"
                        compact
                    />
                </div>
                @endunless

                <div class="px-3 py-3 sm:px-4">
                    <x-ui.line-chart
                        :labels="array_column($revenueTrend, 'label')"
                        :values="$fbRevenueTrend"
                        :currency="$currency"
                        :height="150"
                        :labelInterval="$trendIsHourly ? 3 : null"
                        compact
                    >
                        No {{ strtolower($fbScopeLabel) }} revenue data for this period.
                    </x-ui.line-chart>
                </div>
            @if (! $showHotelSummary)</form>@endif
        </section>

        {{-- Today's F&B split + top products --}}
        <div class="grid gap-6 {{ $topProducts->isNotEmpty() ? 'xl:grid-cols-2' : '' }}">
            <section class="card overflow-hidden">
                <div class="card-money-header">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-ink">Today's {{ $fbScopeLabel }} sales</h3>
                        <p class="text-xs text-ink-muted">{{ now()->format('d M Y') }}</p>
                    </div>
                    <span class="money-total">
                        {{ $currency }} {{ $fmt($fbTodayTotal) }}
                    </span>
                </div>

                @php
                    $fbDivisions = match ($fbScope) {
                        'restaurant' => [
                            ['label' => 'Restaurant', 'value' => $todaySales['restaurant'], 'color' => 'text-amber-700', 'bg' => 'bg-amber-50'],
                        ],
                        'bar' => [
                            ['label' => 'Bar & lounge', 'value' => $todaySales['bar'], 'color' => 'text-sky-700', 'bg' => 'bg-sky-50'],
                        ],
                        default => [
                            ['label' => 'Restaurant',   'value' => $todaySales['restaurant'], 'color' => 'text-amber-700', 'bg' => 'bg-amber-50'],
                            ['label' => 'Bar & lounge', 'value' => $todaySales['bar'],        'color' => 'text-sky-700',   'bg' => 'bg-sky-50'],
                        ],
                    };
                    $fbTodayMax = max(1, $fbTodayTotal);
                @endphp
                <div class="grid divide-y divide-slate-100 {{ count($fbDivisions) > 1 ? 'sm:grid-cols-2 sm:divide-x sm:divide-y-0' : '' }}">
                    @foreach ($fbDivisions as $div)
                        @php $pct = min(100, round($div['value'] / $fbTodayMax * 100)); @endphp
                        <div class="px-4 py-4 sm:px-5">
                            <p class="mb-1 text-xs font-medium text-ink-muted">{{ $div['label'] }}</p>
                            <p class="break-words text-base font-bold {{ $div['color'] }}">{{ $fmt($div['value']) }}</p>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $div['bg'] }} border border-current {{ $div['color'] }}"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 text-xs text-ink-muted sm:px-6">
                    <p>
                        Month to date {{ $fbScopeLabel }}:
                        <span class="font-semibold text-amber-700">{{ $currency }} {{ $fmt($fbMtdTotal) }}</span>
                        @if ($fbScope === 'both')
                            <span class="block text-ink-subtle sm:inline">
                                · Restaurant {{ $fmt($mtdSales['restaurant']) }}
                                · Bar {{ $fmt($mtdSales['bar']) }}
                            </span>
                        @endif
                    </p>
                </div>
            </section>

            @if ($topProducts->isNotEmpty())
            <section class="card card-pad">
                <div class="mb-5">
                    <h3 class="font-semibold text-ink">Top menu items</h3>
                    <p class="text-xs text-ink-muted">{{ now()->format('M Y') }} · {{ strtolower($fbScopeLabel) }} · by quantity sold</p>
                </div>

                @php $maxQty = max(1, $topProducts->max('qty_sold')); @endphp
                <div class="space-y-2.5">
                    @foreach ($topProducts as $item)
                        @php $pct = max(3, round($item->qty_sold / $maxQty * 100)); @endphp
                        <div>
                            <div class="mb-1 flex flex-wrap items-center justify-between gap-x-2 gap-y-0.5">
                                <span class="min-w-0 flex-1 truncate text-xs font-medium text-ink">{{ $item->item_name }}</span>
                                <span class="shrink-0 text-xs text-ink-muted">{{ $item->qty_sold }}x &middot; {{ $currency }} {{ number_format((float) $item->revenue) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $fbScope === 'bar' ? 'bg-sky-400' : 'bg-amber-400' }} transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif
        </div>

        {{-- 7-day F&B mini bar chart --}}
        @if ($recentSnapshots->isNotEmpty())
            @php
                $fbMax = max(1, $recentSnapshots->max(function ($s) use ($fbScope) {
                    return match ($fbScope) {
                        'restaurant' => (float) $s->restaurant,
                        'bar' => (float) $s->bar,
                        default => (float) $s->restaurant + (float) $s->bar,
                    };
                }));
                $barMaxPx = 48;
            @endphp
            <section class="card card-pad">
                <p class="mb-3 text-xs font-medium uppercase tracking-wide text-ink-muted">
                    {{ $recentSnapshots->count() }}-day {{ $fbScopeLabel }} trend
                </p>
                <div class="flex items-end gap-2" style="height: {{ $barMaxPx + 18 }}px">
                    @foreach ($recentSnapshots as $snap)
                        @php
                            $rVal = (float) $snap->restaurant;
                            $bVal = (float) $snap->bar;
                            $rPx  = max(2, (int) round($rVal / $fbMax * $barMaxPx));
                            $bPx  = max(2, (int) round($bVal / $fbMax * $barMaxPx));
                            $dayLbl = \Carbon\Carbon::parse($snap->snapshot_date)->format('D');
                            $tip = match ($fbScope) {
                                'restaurant' => "{$dayLbl}: Restaurant {$currency} ".number_format($rVal),
                                'bar' => "{$dayLbl}: Bar {$currency} ".number_format($bVal),
                                default => "{$dayLbl}: Restaurant {$currency} ".number_format($rVal).' · Bar '.$currency.' '.number_format($bVal),
                            };
                        @endphp
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="flex w-full items-end justify-center gap-px"
                                 style="height: {{ $barMaxPx }}px"
                                 title="{{ $tip }}">
                                @if ($fbScope !== 'bar')
                                    <div class="flex-1 rounded-t bg-amber-400/80 transition hover:bg-amber-400"
                                         style="height: {{ $rPx }}px"></div>
                                @endif
                                @if ($fbScope !== 'restaurant')
                                    <div class="flex-1 rounded-t bg-sky-400/80 transition hover:bg-sky-400"
                                         style="height: {{ $bPx }}px"></div>
                                @endif
                            </div>
                            <span class="text-[10px] text-ink-muted">{{ $dayLbl }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($fbScope === 'both')
                    <div class="mt-2 flex gap-4 text-[10px] text-ink-subtle">
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block size-2 rounded-full bg-amber-400"></span> Restaurant
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block size-2 rounded-full bg-sky-400"></span> Bar &amp; lounge
                        </span>
                    </div>
                @endif
            </section>
        @endif
        @endcanany
    </div>
    @endif

    {{-- ===== QUICK ACTIONS ===== --}}
    <section class="card card-pad">
        <h2 class="font-semibold text-ink">Quick actions</h2>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:flex sm:flex-wrap">
            @if ($showHotelSummary)
                @can('view-availability')
                    <a href="{{ route('tenant.availability') }}" class="btn-primary w-full sm:w-auto">Search availability</a>
                @endcan
                @can('view-reservations')
                    <a href="{{ route('tenant.reservations.index') }}" class="btn-outline w-full sm:w-auto">Reservations</a>
                    <a href="{{ route('tenant.booked-list.index') }}" class="btn-outline w-full sm:w-auto">Booked list</a>
                @endcan
                @can('view-rooms')
                    <a href="{{ route('tenant.rooms.index') }}" class="btn-outline w-full sm:w-auto">Room board</a>
                @endcan
                @can('view-guests')
                    <a href="{{ route('tenant.guests.index') }}" class="btn-outline w-full sm:w-auto">Guests</a>
                @endcan
            @endif
            @if ($showFbSummary)
                @can('view-orders')
                    <a href="{{ route('tenant.restaurant.index') }}" class="{{ $showHotelSummary ? 'btn-outline' : 'btn-primary' }} w-full sm:w-auto">Restaurant</a>
                    <a href="{{ route('tenant.bar.index') }}" class="btn-outline w-full sm:w-auto">Bar</a>
                @endcan
                @can('view-till')
                    <a href="{{ route('tenant.till.index') }}" class="btn-outline w-full sm:w-auto">Till</a>
                @endcan
            @endif
            @canany(['view-reports', 'view-fb-reports', 'view-facility-reports'])
                @if (\App\Support\TenantFeatures::allowsReportsHub())
                    <a href="{{ route('tenant.reports') }}" class="btn-outline w-full sm:w-auto">Reports</a>
                @endif
            @endcanany
        </div>
    </section>

</div>
</x-layouts.app>
