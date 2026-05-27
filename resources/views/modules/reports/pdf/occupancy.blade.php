@extends('modules.reports.pdf.layout')

@section('title', 'Occupancy Report')
@section('period', $report['from'].' — '.$report['to'])

@section('content')
@php
    $t = $report['totals'];
    $currency = config('nexstay.currency.default', 'TZS');
    $fmt = fn(float $v) => number_format($v, 0);
@endphp

{{-- KPI summary --}}
<table class="kpis">
    <tr>
        <td class="kpi"><p class="kpi-l">Rooms available</p><p class="kpi-v">{{ $t['rooms_available'] }}</p></td>
        <td class="kpi"><p class="kpi-l">Room nights sold</p><p class="kpi-v">{{ number_format($t['room_nights_sold']) }}</p></td>
        <td class="kpi"><p class="kpi-l">Avg occupancy</p><p class="kpi-v">{{ $t['avg_occupancy_pct'] }}%</p></td>
        <td class="kpi"><p class="kpi-l">Total revenue</p><p class="kpi-v green">{{ $currency }} {{ $fmt($t['total_revenue']) }}</p></td>
        <td class="kpi"><p class="kpi-l">ADR</p><p class="kpi-v">{{ $currency }} {{ $fmt($t['adr']) }}</p></td>
        <td class="kpi"><p class="kpi-l">RevPAR</p><p class="kpi-v">{{ $currency }} {{ $fmt($t['revpar']) }}</p></td>
    </tr>
</table>

<h2>Daily Breakdown</h2>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th class="r">Rooms Available</th>
            <th class="r">Rooms Occupied</th>
            <th class="r">Occupancy %</th>
            <th class="r">Revenue ({{ $currency }})</th>
            <th class="r">ADR</th>
            <th class="r">RevPAR</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($report['rows'] as $row)
            <tr>
                <td>{{ $row['date'] }}</td>
                <td class="r">{{ $row['rooms_available'] }}</td>
                <td class="r">{{ $row['rooms_occupied'] }}</td>
                <td class="r">{{ $row['occupancy_pct'] }}%</td>
                <td class="r green">{{ $fmt($row['revenue']) }}</td>
                <td class="r">{{ $fmt($row['adr']) }}</td>
                <td class="r">{{ $fmt($row['revpar']) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="tr-total">
            <td>TOTAL / AVG</td>
            <td class="r">{{ $t['rooms_available'] }}</td>
            <td class="r">{{ $t['room_nights_sold'] }}</td>
            <td class="r">{{ $t['avg_occupancy_pct'] }}%</td>
            <td class="r green">{{ $currency }} {{ $fmt($t['total_revenue']) }}</td>
            <td class="r">{{ $fmt($t['adr']) }}</td>
            <td class="r">{{ $fmt($t['revpar']) }}</td>
        </tr>
    </tfoot>
</table>
@endsection
