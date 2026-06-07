<x-layouts.app active-nav="room-reservations-summary-report" title="Room reservations summary" subtitle="Reservation-level room revenue grouped by room type and status">

    @php
        $fmt = fn (float $v) => number_format($v, 0, '.', ',');
        $ep = array_filter([
            'from'          => $from->format('Y-m-d'),
            'to'            => $to->format('Y-m-d'),
            'room_type_id'  => $roomTypeId,
            'status'        => $status,
        ], fn ($value) => $value !== null && $value !== '');
        $statuses = [
            'confirmed'   => 'Confirmed',
            'checked_in'  => 'Checked in',
            'checked_out' => 'Checked out',
            'inquiry'     => 'Inquiry',
            'cancelled'   => 'Cancelled',
            'no_show'     => 'No show',
        ];
    @endphp

    <form method="GET" class="mb-6 space-y-4">
        <x-ui.date-range-filter :from="$from->format('Y-m-d')" :to="$to->format('Y-m-d')">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-ink-muted">Room type</label>
                <select name="room_type_id" class="input-field w-auto min-w-[180px]" onchange="this.form.submit()">
                    <option value="">All room types</option>
                    @foreach ($roomTypes as $roomType)
                        <option value="{{ $roomType->id }}" @selected($roomTypeId === $roomType->id)>{{ $roomType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-ink-muted">Status</label>
                <select name="status" class="input-field w-auto min-w-[180px]" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('tenant.reports.room-reservations-summary.export-excel', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20">Excel</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.room-reservations-summary.export-pdf', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20">PDF</a>
            </div>
        </x-ui.date-range-filter>
    </form>

    <div class="card mb-6 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-base font-semibold text-ink">{{ $report['hotel_name'] }}</p>
                <p class="mt-1 text-xs text-ink-muted">
                    Date: {{ $report['from'] }} To {{ $report['to'] }}
                    &nbsp;|&nbsp; Room type: {{ $report['room_type_filter'] }}
                    &nbsp;|&nbsp; Status: {{ $report['status_filter'] }}
                </p>
            </div>
            <p class="text-sm font-semibold text-ink">Room Reservations Summary</p>
        </div>
    </div>

    @if ($report['categories'] === [])
        <div class="card px-6 py-12 text-center text-sm text-ink-muted">
            No room reservations with check-in in this period.
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <tr>
                            <th class="px-4 py-3 text-left">Guest / booking</th>
                            <th class="px-4 py-3 text-right">Nights</th>
                            <th class="px-4 py-3 text-right">Daily rate</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Paid</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                            <th class="px-4 py-3 text-right">Tax</th>
                            <th class="px-4 py-3 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($report['categories'] as $category)
                            <tr class="bg-slate-100/80">
                                <td colspan="8" class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-ink">
                                    {{ $category['name'] }}
                                </td>
                            </tr>

                            @foreach ($category['subcategories'] as $subcategory)
                                <tr>
                                    <td colspan="8" class="px-4 py-1 text-xs italic text-ink-muted">{{ $subcategory['name'] }}</td>
                                </tr>

                                @foreach ($subcategory['items'] as $item)
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-4 py-2">
                                            <span class="font-medium text-ink">{{ $item['name'] }}</span>
                                            <span class="block text-xs text-ink-muted">Room {{ $item['room_number'] }}</span>
                                        </td>
                                        <td class="px-4 py-2 text-right text-ink-muted">{{ number_format($item['quantity']) }}</td>
                                        <td class="px-4 py-2 text-right">{{ $fmt($item['price_avg']) }}</td>
                                        <td class="px-4 py-2 text-right">{{ $fmt($item['amount']) }}</td>
                                        <td class="px-4 py-2 text-right text-emerald-700">{{ $fmt($item['discount']) }}</td>
                                        <td class="px-4 py-2 text-right text-amber-700">{{ $fmt($item['net_rate']) }}</td>
                                        <td class="px-4 py-2 text-right">{{ $fmt($item['tax']) }}</td>
                                        <td class="px-4 py-2 text-right font-semibold text-ink">{{ $fmt($item['total_amount']) }}</td>
                                    </tr>
                                @endforeach

                                @php $sub = $subcategory['subtotal']; @endphp
                                <tr class="bg-slate-50 text-xs font-semibold">
                                    <td class="px-4 py-2 text-ink-muted">Subcategory sub total</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($sub['quantity']) }}</td>
                                    <td></td>
                                    <td class="px-4 py-2 text-right">{{ $fmt($sub['amount']) }}</td>
                                    <td class="px-4 py-2 text-right">{{ $fmt($sub['discount']) }}</td>
                                    <td class="px-4 py-2 text-right">{{ $fmt($sub['net_rate']) }}</td>
                                    <td class="px-4 py-2 text-right">{{ $fmt($sub['tax']) }}</td>
                                    <td class="px-4 py-2 text-right">{{ $fmt($sub['total_amount']) }}</td>
                                </tr>
                            @endforeach

                            @php $cat = $category['subtotal']; @endphp
                            <tr class="bg-slate-100 text-xs font-semibold">
                                <td class="px-4 py-2 text-ink">Category sub total</td>
                                <td class="px-4 py-2 text-right">{{ number_format($cat['quantity']) }}</td>
                                <td></td>
                                <td class="px-4 py-2 text-right">{{ $fmt($cat['amount']) }}</td>
                                <td class="px-4 py-2 text-right">{{ $fmt($cat['discount']) }}</td>
                                <td class="px-4 py-2 text-right">{{ $fmt($cat['net_rate']) }}</td>
                                <td class="px-4 py-2 text-right">{{ $fmt($cat['tax']) }}</td>
                                <td class="px-4 py-2 text-right">{{ $fmt($cat['total_amount']) }}</td>
                            </tr>
                        @endforeach

                        @php $grand = $report['grand_total']; @endphp
                        <tr class="border-t-2 border-slate-300 bg-slate-50 text-sm font-bold">
                            <td class="px-4 py-3 text-ink">Grand total</td>
                            <td class="px-4 py-3 text-right">{{ number_format($grand['quantity']) }}</td>
                            <td></td>
                            <td class="px-4 py-3 text-right">{{ $fmt($grand['amount']) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700">{{ $fmt($grand['discount']) }}</td>
                            <td class="px-4 py-3 text-right text-amber-700">{{ $fmt($grand['net_rate']) }}</td>
                            <td class="px-4 py-3 text-right">{{ $fmt($grand['tax']) }}</td>
                            <td class="px-4 py-3 text-right text-indigo-700">{{ $fmt($grand['total_amount']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="mt-4 text-xs text-ink-subtle">
            Reservations are included by check-in date. Inquiry bookings show zero recognized revenue until confirmed. Amount is gross stay value; paid and balance follow your property payment mode.
        </p>
    @endif

</x-layouts.app>
