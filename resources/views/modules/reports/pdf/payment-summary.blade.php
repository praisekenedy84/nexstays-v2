@extends('modules.reports.pdf.layout')

@section('title', 'Payment Collection Summary')
@section('period', $report['from'].' — '.$report['to'])

@section('content')
@php
    $currency = config('nexstay.currency.default', 'TZS');
    $fmt = fn(float $v) => number_format($v, 0);
    $methodLabels = ['cash' => 'Cash', 'card' => 'Card', 'mobile_money' => 'Mobile Money'];
@endphp

<h2>Payments by Method</h2>
<table>
    <thead>
        <tr>
            <th>Payment Method</th>
            <th class="r">Transactions</th>
            <th class="r">Amount ({{ $currency }})</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($report['by_method'] as $row)
            <tr>
                <td>{{ $methodLabels[$row['method']] ?? ucwords(str_replace('_', ' ', $row['method'])) }}</td>
                <td class="r">{{ number_format($row['count']) }}</td>
                <td class="r green">{{ $fmt($row['total']) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="tr-total">
            <td>TOTAL</td>
            <td class="r">{{ $report['by_method']->sum('count') }}</td>
            <td class="r green">{{ $currency }} {{ $fmt($report['grand_total']) }}</td>
        </tr>
    </tfoot>
</table>

<h2>Source Breakdown</h2>
<table>
    <tbody>
        <tr>
            <td>Folio (room account)</td>
            <td class="r">{{ $currency }} {{ $fmt($report['folio_total']) }}</td>
        </tr>
        <tr>
            <td>Direct POS</td>
            <td class="r">{{ $currency }} {{ $fmt($report['direct_total']) }}</td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="tr-total">
            <td>Grand Total</td>
            <td class="r green">{{ $currency }} {{ $fmt($report['grand_total']) }}</td>
        </tr>
    </tfoot>
</table>
@endsection
