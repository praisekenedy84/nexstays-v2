@extends('modules.reports.pdf.layout')

@section('title', 'Room Payments & Accounting')
@section('period', $report['from'].' — '.$report['to'])

@section('content')
@php
    $currency = config('nexstay.currency.default', 'TZS');
    $fmt = fn(float $v) => number_format((float) $v, 0);
    $t = $report['totals'];
@endphp

{{-- Summary KPIs --}}
<table class="kpis">
    <tr>
        <td class="kpi"><p class="kpi-l">Reservations</p><p class="kpi-v">{{ $t['reservations'] }}</p></td>
        <td class="kpi"><p class="kpi-l">Room Nights</p><p class="kpi-v">{{ $t['room_nights'] }}</p></td>
        <td class="kpi"><p class="kpi-l">Room Revenue</p><p class="kpi-v green">{{ $currency }} {{ $fmt($t['room_revenue']) }}</p></td>
        <td class="kpi"><p class="kpi-l">Payments Received</p><p class="kpi-v green">{{ $currency }} {{ $fmt($t['payments_received']) }}</p></td>
        <td class="kpi"><p class="kpi-l">Outstanding</p><p class="kpi-v {{ (float)$t['outstanding_balance'] > 0 ? 'red' : 'green' }}">{{ $currency }} {{ $fmt($t['outstanding_balance']) }}</p></td>
    </tr>
</table>

<h2>Guest Payment Details</h2>
<table>
    <thead>
        <tr>
            <th>Booking Ref</th>
            <th>Guest</th>
            <th>Room</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th class="r">Nights</th>
            <th class="r">Room Revenue</th>
            <th class="r">Folio Charges</th>
            <th class="r">Paid</th>
            <th class="r">Outstanding</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($report['rows'] as $row)
            @php $outstanding = (float) $row['outstanding_balance']; @endphp
            <tr>
                <td style="font-size:7.5pt; font-weight:bold;">{{ $row['reservation']->booking_ref }}</td>
                <td>{{ $row['guest_name'] }}</td>
                <td class="c">{{ $row['room_number'] }}</td>
                <td>{{ $row['reservation']->check_in_date?->format('d M Y') }}</td>
                <td>{{ $row['reservation']->check_out_date?->format('d M Y') }}</td>
                <td class="r">{{ $row['stay_nights'] }}</td>
                <td class="r">{{ $fmt($row['room_revenue']) }}</td>
                <td class="r">{{ $fmt($row['folio_charges']) }}</td>
                <td class="r green">{{ $fmt($row['payments_received']) }}</td>
                <td class="r {{ $outstanding > 0 ? 'red' : 'green' }}">{{ $fmt($outstanding) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="tr-total">
            <td colspan="5">TOTAL</td>
            <td class="r">{{ $t['room_nights'] }}</td>
            <td class="r green">{{ $currency }} {{ $fmt($t['room_revenue']) }}</td>
            <td class="r amber">{{ $currency }} {{ $fmt($t['folio_charges']) }}</td>
            <td class="r green">{{ $currency }} {{ $fmt($t['payments_received']) }}</td>
            <td class="r {{ (float)$t['outstanding_balance'] > 0 ? 'red' : 'green' }}">{{ $currency }} {{ $fmt($t['outstanding_balance']) }}</td>
        </tr>
    </tfoot>
</table>
<p style="font-size:7pt; color:#94a3b8;">All amounts in {{ $currency }}.</p>
@endsection
