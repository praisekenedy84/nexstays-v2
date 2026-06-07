<x-layouts.app active-nav="menu-item-sales-summary-report" title="Menu item sales summary" subtitle="Item-level F&amp;B sales grouped by category">

    @php
        $fmt = fn (float $v) => number_format($v, 0, '.', ',');
        $ep = array_filter([
            'from'        => $from->format('Y-m-d'),
            'to'          => $to->format('Y-m-d'),
            'outlet_id'   => $outletId,
            'category_id' => $categoryId,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <form method="GET" class="mb-6 space-y-4">
        <x-ui.date-range-filter :from="$from->format('Y-m-d')" :to="$to->format('Y-m-d')">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-ink-muted">Outlet</label>
                <select name="outlet_id" class="input-field w-auto min-w-[180px]" onchange="this.form.submit()">
                    <option value="">All outlets</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected($outletId === $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-ink-muted">Menu category</label>
                <select name="category_id" class="input-field w-auto min-w-[180px]" onchange="this.form.submit()">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($categoryId === $category->id)>
                            {{ $category->name }}@if ($category->outlet) ({{ $category->outlet->name }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('tenant.reports.menu-item-sales-summary.export-excel', $ep) }}"
                   class="px-3 py-2 text-sm font-medium text-ink transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20">Excel</a>
                <span class="w-px bg-slate-200 dark:bg-slate-700"></span>
                <a href="{{ route('tenant.reports.menu-item-sales-summary.export-pdf', $ep) }}"
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
                    &nbsp;|&nbsp; Outlet: {{ $report['outlet_name'] }}
                    &nbsp;|&nbsp; Category: {{ $report['category_filter'] }}
                </p>
            </div>
            <p class="text-sm font-semibold text-ink">Menu Item Sales Summary</p>
        </div>
    </div>

    @if ($report['categories'] === [])
        <div class="card px-6 py-12 text-center text-sm text-ink-muted">
            No menu item sales recorded in this period.
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <tr>
                            <th class="px-4 py-3 text-left">Menu item</th>
                            <th class="px-4 py-3 text-right">Quantity</th>
                            <th class="px-4 py-3 text-right">Price (avg)</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Discount</th>
                            <th class="px-4 py-3 text-right">Net rate</th>
                            <th class="px-4 py-3 text-right">Tax</th>
                            <th class="px-4 py-3 text-right">Total amount</th>
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
                                        <td class="px-4 py-2 text-ink">{{ $item['name'] }}</td>
                                        <td class="px-4 py-2 text-right text-ink-muted">{{ number_format($item['quantity']) }}</td>
                                        <td class="px-4 py-2 text-right">{{ $fmt($item['price_avg']) }}</td>
                                        <td class="px-4 py-2 text-right">{{ $fmt($item['amount']) }}</td>
                                        <td class="px-4 py-2 text-right">{{ $fmt($item['discount']) }}</td>
                                        <td class="px-4 py-2 text-right">{{ $fmt($item['net_rate']) }}</td>
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
                            <td class="px-4 py-3 text-right">{{ $fmt($grand['discount']) }}</td>
                            <td class="px-4 py-3 text-right">{{ $fmt($grand['net_rate']) }}</td>
                            <td class="px-4 py-3 text-right">{{ $fmt($grand['tax']) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700">{{ $fmt($grand['total_amount']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</x-layouts.app>
