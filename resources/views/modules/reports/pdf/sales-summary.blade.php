@extends('modules.reports.pdf.layout')

@section('title', 'Sales Summary')
@section('period', $report['period_label'].' ('.$report['from'].' — '.$report['to'].')')

@section('content')
@php
    $currency = config('nexstay.currency.default', 'TZS');
    $fmt = fn(float $v) => number_format($v, 0);
    $summary = $report['summary'];
@endphp

<table class="kpis" width="100%">
    <tr>
        <td class="kpi" width="33%">
            <div class="kpi-l">Total posted sales</div>
            <div class="kpi-v green">{{ $currency }} {{ $fmt($summary['total']) }}</div>
        </td>
        <td class="kpi" width="33%">
            <div class="kpi-l">Payments collected</div>
            <div class="kpi-v">{{ $currency }} {{ $fmt($summary['payments_collected']) }}</div>
        </td>
        <td class="kpi" width="33%">
            <div class="kpi-l">Room nights occupied</div>
            <div class="kpi-v">{{ $summary['room_nights'] }}</div>
        </td>
    </tr>
</table>

<h2>Division Breakdown</h2>
<table>
    <thead>
        <tr>
            <th>Division</th>
            <th class="r">Amount ({{ $currency }})</th>
            <th class="r">Share</th>
        </tr>
    </thead>
    <tbody>
        @foreach ([
            'Rooms (posted)' => $summary['rooms'],
            'Restaurant' => $summary['restaurant'],
            'Bar & lounge' => $summary['bar'],
            'Ancillary' => $summary['ancillary'],
        ] as $label => $amount)
            @php
                $share = $summary['total'] > 0 ? round($amount / $summary['total'] * 100, 1) : 0;
            @endphp
            <tr>
                <td>{{ $label }}</td>
                <td class="r">{{ $fmt($amount) }}</td>
                <td class="r muted">{{ $share }}%</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="tr-total">
            <td>Total</td>
            <td class="r green">{{ $currency }} {{ $fmt($summary['total']) }}</td>
            <td class="r">100%</td>
        </tr>
    </tfoot>
</table>

@if ($report['daily_rows'] !== [])
    <h2>Daily Breakdown</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th class="r">Rooms</th>
                <th class="r">Restaurant</th>
                <th class="r">Bar</th>
                <th class="r">Ancillary</th>
                <th class="r">Total</th>
                <th class="r">Payments</th>
                <th class="r">Nights</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['daily_rows'] as $row)
                <tr>
                    <td>{{ $row['date_label'] }}</td>
                    <td class="r">{{ $fmt($row['rooms']) }}</td>
                    <td class="r">{{ $fmt($row['restaurant']) }}</td>
                    <td class="r">{{ $fmt($row['bar']) }}</td>
                    <td class="r">{{ $fmt($row['ancillary']) }}</td>
                    <td class="r">{{ $fmt($row['total']) }}</td>
                    <td class="r">{{ $fmt($row['payments_collected']) }}</td>
                    <td class="r">{{ $row['room_nights'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tr-total">
                <td>Period total</td>
                <td class="r">{{ $fmt($summary['rooms']) }}</td>
                <td class="r">{{ $fmt($summary['restaurant']) }}</td>
                <td class="r">{{ $fmt($summary['bar']) }}</td>
                <td class="r">{{ $fmt($summary['ancillary']) }}</td>
                <td class="r green">{{ $currency }} {{ $fmt($summary['total']) }}</td>
                <td class="r">{{ $fmt($summary['payments_collected']) }}</td>
                <td class="r">{{ $summary['room_nights'] }}</td>
            </tr>
        </tfoot>
    </table>
@endif
@endsection
