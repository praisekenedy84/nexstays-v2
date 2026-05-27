@extends('modules.reports.pdf.layout')

@section('title', 'Room Reservations Finance')
@section('period', $report['from'].' — '.$report['to'])

@section('content')
@php
    $currency = config('nexstay.currency.default', 'TZS');
    $fmt = fn(float $v) => number_format((float) $v, 0);
@endphp

<table class="kpis">
    <tr>
        <td class="kpi"><p class="kpi-l">Total Reservations</p><p class="kpi-v">{{ number_format($report['total_reservations']) }}</p></td>
        <td class="kpi"><p class="kpi-l">Room Nights</p><p class="kpi-v">{{ number_format($report['room_nights']) }}</p></td>
        <td class="kpi"><p class="kpi-l">Revenue Recognized</p><p class="kpi-v green">{{ $currency }} {{ $fmt($report['projected_room_revenue']) }}</p></td>
        <td class="kpi"><p class="kpi-l">Payments Collected</p><p class="kpi-v green">{{ $currency }} {{ $fmt($report['deposits_collected']) }}</p></td>
        <td class="kpi"><p class="kpi-l">Balance on Arrival</p><p class="kpi-v" style="color:#2563eb;">{{ $currency }} {{ $fmt($report['balance_expected_on_arrival']) }}</p></td>
    </tr>
</table>

<h2>Accounting Summary</h2>
<table>
    <tbody>
        <tr>
            <td>Room Revenue Recognized</td>
            <td class="r green">{{ $currency }} {{ $fmt($report['projected_room_revenue']) }}</td>
        </tr>
        <tr>
            <td>Payments Collected (prepaid)</td>
            <td class="r green">{{ $currency }} {{ $fmt($report['deposits_collected']) }}</td>
        </tr>
        <tr class="tr-total">
            <td>Balance Expected on Arrival</td>
            <td class="r" style="color:#2563eb;"><strong>{{ $currency }} {{ $fmt($report['balance_expected_on_arrival']) }}</strong></td>
        </tr>
    </tbody>
</table>

<h2>Reservation Status Mix</h2>
<table>
    <thead>
        <tr>
            <th>Status</th>
            <th class="r">Count</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($report['status_counts'] as $status => $count)
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $status)) }}</td>
                <td class="r">{{ number_format((int) $count) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="tr-total">
            <td>Total</td>
            <td class="r">{{ number_format(array_sum($report['status_counts'])) }}</td>
        </tr>
    </tfoot>
</table>
<p style="font-size:7pt; color:#94a3b8;">Inquiry reservations are excluded from recognized revenue and included in balance expected on arrival until confirmed.</p>
@endsection
