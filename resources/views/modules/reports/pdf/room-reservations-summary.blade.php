@extends('modules.reports.pdf.layout')

@section('title', 'Room Reservations Summary')
@section('period', $report['from'].' To '.$report['to'].' · '.$report['room_type_filter'].' · '.$report['status_filter'])

@section('content')
@php
    $fmt = fn(float $v) => number_format($v, 0, '.', ',');
@endphp

<p class="meta" style="margin-bottom: 10px;">{{ $report['hotel_name'] }}</p>

@if ($report['categories'] === [])
    <p class="muted">No room reservations with check-in in this period.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Guest / booking</th>
                <th class="r">Nights</th>
                <th class="r">Daily rate</th>
                <th class="r">Amount</th>
                <th class="r">Paid</th>
                <th class="r">Balance</th>
                <th class="r">Tax</th>
                <th class="r">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['categories'] as $category)
                <tr class="tr-section">
                    <td colspan="8">{{ $category['name'] }}</td>
                </tr>

                @foreach ($category['subcategories'] as $subcategory)
                    <tr>
                        <td colspan="8" class="muted" style="font-style: italic;">{{ $subcategory['name'] }}</td>
                    </tr>

                    @foreach ($subcategory['items'] as $item)
                        <tr>
                            <td>{{ $item['name'] }} · Rm {{ $item['room_number'] }}</td>
                            <td class="r">{{ number_format($item['quantity']) }}</td>
                            <td class="r">{{ $fmt($item['price_avg']) }}</td>
                            <td class="r">{{ $fmt($item['amount']) }}</td>
                            <td class="r green">{{ $fmt($item['discount']) }}</td>
                            <td class="r amber">{{ $fmt($item['net_rate']) }}</td>
                            <td class="r">{{ $fmt($item['tax']) }}</td>
                            <td class="r">{{ $fmt($item['total_amount']) }}</td>
                        </tr>
                    @endforeach

                    @php $sub = $subcategory['subtotal']; @endphp
                    <tr style="background: #f8fafc;">
                        <td class="muted">Subcategory sub total</td>
                        <td class="r">{{ number_format($sub['quantity']) }}</td>
                        <td></td>
                        <td class="r">{{ $fmt($sub['amount']) }}</td>
                        <td class="r">{{ $fmt($sub['discount']) }}</td>
                        <td class="r">{{ $fmt($sub['net_rate']) }}</td>
                        <td class="r">{{ $fmt($sub['tax']) }}</td>
                        <td class="r">{{ $fmt($sub['total_amount']) }}</td>
                    </tr>
                @endforeach

                @php $cat = $category['subtotal']; @endphp
                <tr style="background: #f1f5f9;">
                    <td>Category sub total</td>
                    <td class="r">{{ number_format($cat['quantity']) }}</td>
                    <td></td>
                    <td class="r">{{ $fmt($cat['amount']) }}</td>
                    <td class="r">{{ $fmt($cat['discount']) }}</td>
                    <td class="r">{{ $fmt($cat['net_rate']) }}</td>
                    <td class="r">{{ $fmt($cat['tax']) }}</td>
                    <td class="r">{{ $fmt($cat['total_amount']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @php $grand = $report['grand_total']; @endphp
            <tr class="tr-total">
                <td>Grand total</td>
                <td class="r">{{ number_format($grand['quantity']) }}</td>
                <td></td>
                <td class="r">{{ $fmt($grand['amount']) }}</td>
                <td class="r green">{{ $fmt($grand['discount']) }}</td>
                <td class="r amber">{{ $fmt($grand['net_rate']) }}</td>
                <td class="r">{{ $fmt($grand['tax']) }}</td>
                <td class="r green">{{ $fmt($grand['total_amount']) }}</td>
            </tr>
        </tfoot>
    </table>
@endif
@endsection
