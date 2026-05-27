@extends('modules.reports.pdf.layout')

@section('title', 'F&B Profitability Report')
@section('period', $data['from'].' — '.$data['to'])

@section('content')
@php
    $currency = config('nexstay.currency.default', 'TZS');
    $fmt = fn(float $v) => number_format($v, 0);
    $r = $data;
@endphp

<h2>Profitability Matrix</h2>
<table>
    <thead>
        <tr>
            <th>Line</th>
            <th class="r">Food ({{ $currency }})</th>
            <th class="r">Drinks ({{ $currency }})</th>
            <th class="r">Total ({{ $currency }})</th>
        </tr>
    </thead>
    <tbody>
        <tr style="background:#f0fdf4;">
            <td><strong>Revenue</strong></td>
            <td class="r green">{{ $fmt($r['revenue']['food']) }}</td>
            <td class="r green">{{ $fmt($r['revenue']['drinks']) }}</td>
            <td class="r green"><strong>{{ $fmt($r['revenue']['total']) }}</strong></td>
        </tr>
        <tr>
            <td class="muted">COGS (theoretical)</td>
            <td class="r red">{{ $fmt($r['cogs']['food']) }}</td>
            <td class="r red">{{ $fmt($r['cogs']['drinks']) }}</td>
            <td class="r red">{{ $fmt($r['cogs']['total']) }}</td>
        </tr>
        <tr style="background:#f8fafc;">
            <td><strong>Gross Profit</strong> <span class="muted" style="font-size:7.5pt;">({{ $r['gross_margin_pct'] }}% margin)</span></td>
            <td class="r {{ ($r['revenue']['food'] - $r['cogs']['food']) >= 0 ? 'green' : 'red' }}">{{ $fmt($r['revenue']['food'] - $r['cogs']['food']) }}</td>
            <td class="r {{ ($r['revenue']['drinks'] - $r['cogs']['drinks']) >= 0 ? 'green' : 'red' }}">{{ $fmt($r['revenue']['drinks'] - $r['cogs']['drinks']) }}</td>
            <td class="r {{ $r['gross_profit'] >= 0 ? 'green' : 'red' }}"><strong>{{ $fmt($r['gross_profit']) }}</strong></td>
        </tr>
        <tr>
            <td class="muted">Stock Purchases (received)</td>
            <td class="r amber">{{ $fmt($r['purchases']['food']) }}</td>
            <td class="r amber">{{ $fmt($r['purchases']['drinks']) }}</td>
            <td class="r amber">{{ $fmt($r['purchases']['total']) }}</td>
        </tr>
        <tr>
            <td class="muted">Outlet Expenses</td>
            <td class="r" colspan="2"></td>
            <td class="r amber">{{ $fmt($r['outlet_expenses']) }}</td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="tr-total">
            <td>Net Contribution</td>
            <td colspan="2"></td>
            <td class="r {{ $r['net_contribution'] >= 0 ? 'green' : 'red' }}">{{ $currency }} {{ $fmt($r['net_contribution']) }}</td>
        </tr>
    </tfoot>
</table>

@if ($r['top_items']->isNotEmpty())
<h2>Top Selling Items</h2>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Item</th>
            <th>Category</th>
            <th>Outlet</th>
            <th class="r">Qty</th>
            <th class="r">Revenue</th>
            <th class="r">COGS</th>
            <th class="r">Profit</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($r['top_items'] as $i => $item)
            <tr>
                <td class="c muted">{{ $i + 1 }}</td>
                <td><strong>{{ $item['name'] }}</strong></td>
                <td class="muted">{{ $item['category'] }}</td>
                <td class="muted" style="text-transform:uppercase; font-size:7.5pt;">{{ $item['outlet_type'] }}</td>
                <td class="r">{{ number_format($item['qty_sold']) }}</td>
                <td class="r green">{{ $fmt($item['revenue']) }}</td>
                <td class="r red">{{ $fmt($item['cogs']) }}</td>
                <td class="r {{ $item['profit'] >= 0 ? 'green' : 'red' }}">{{ $fmt($item['profit']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

@if ($r['top_room_types']->isNotEmpty())
@php $totalRoomRev = $r['top_room_types']->sum('revenue'); @endphp
<h2>Room Type Performance</h2>
<table>
    <thead>
        <tr>
            <th>Room Type</th>
            <th class="r">Reservations</th>
            <th class="r">Room Nights</th>
            <th class="r">Revenue ({{ $currency }})</th>
            <th class="r">Avg/Night</th>
            <th class="r">Share %</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($r['top_room_types'] as $rt)
            @php
                $avgNight = $rt['room_nights'] > 0 ? $rt['revenue'] / $rt['room_nights'] : 0;
                $share = $totalRoomRev > 0 ? round($rt['revenue'] / $totalRoomRev * 100) : 0;
            @endphp
            <tr>
                <td>{{ $rt['name'] }}</td>
                <td class="r">{{ $rt['reservations'] }}</td>
                <td class="r">{{ $rt['room_nights'] }}</td>
                <td class="r green">{{ $fmt($rt['revenue']) }}</td>
                <td class="r muted">{{ $fmt($avgNight) }}</td>
                <td class="r muted">{{ $share }}%</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="tr-total">
            <td>Total</td>
            <td class="r">{{ $r['top_room_types']->sum('reservations') }}</td>
            <td class="r">{{ $r['top_room_types']->sum('room_nights') }}</td>
            <td class="r green">{{ $currency }} {{ $fmt($totalRoomRev) }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>
@endif

<p style="font-size:7pt; color:#94a3b8;">All amounts in {{ $currency }}. COGS is theoretical (menu item cost × qty sold). Excludes cancelled and no-show reservations.</p>
@endsection
