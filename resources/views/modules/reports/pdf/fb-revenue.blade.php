@extends('modules.reports.pdf.layout')

@section('title', 'F&B Revenue Split')
@section('period', $revenue['from'].' — '.$revenue['to'])

@section('content')
@php
    $currency = config('nexstay.currency.default', 'TZS');
    $fmt = fn(float $v) => number_format($v, 0);
@endphp

<table class="kpis">
    <tr>
        <td class="kpi"><p class="kpi-l">Food (Restaurant)</p><p class="kpi-v amber">{{ $currency }} {{ $fmt((float)$revenue['food']) }}</p></td>
        <td class="kpi"><p class="kpi-l">Drinks (Bar + Lounge)</p><p class="kpi-v" style="color:#0369a1;">{{ $currency }} {{ $fmt((float)$revenue['drinks']) }}</p></td>
        <td class="kpi"><p class="kpi-l">Total F&amp;B</p><p class="kpi-v green">{{ $currency }} {{ $fmt((float)$revenue['total']) }}</p></td>
    </tr>
</table>

<h2>Revenue Breakdown</h2>
<table>
    <thead>
        <tr>
            <th>Category</th>
            <th class="r">Amount ({{ $currency }})</th>
            <th class="r">Share %</th>
        </tr>
    </thead>
    <tbody>
        @php $total = max(1, (float) $revenue['total']); @endphp
        <tr>
            <td>Food (Restaurant)</td>
            <td class="r amber">{{ $fmt((float)$revenue['food']) }}</td>
            <td class="r muted">{{ round((float)$revenue['food'] / $total * 100) }}%</td>
        </tr>
        <tr>
            <td>Drinks (Bar + Lounge)</td>
            <td class="r" style="color:#0369a1;">{{ $fmt((float)$revenue['drinks']) }}</td>
            <td class="r muted">{{ round((float)$revenue['drinks'] / $total * 100) }}%</td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="tr-total">
            <td>Total F&amp;B</td>
            <td class="r green">{{ $currency }} {{ $fmt((float)$revenue['total']) }}</td>
            <td class="r">100%</td>
        </tr>
    </tfoot>
</table>
<p style="font-size:7pt; color:#94a3b8;">Drinks includes bar folio transactions and lounge charges. Revenue includes both folio-posted charges and direct POS cash payments.</p>
@endsection
