@extends('modules.reports.pdf.layout')

@section('title', $report['facility_label'] . ' Attendance Report')

@section('content')
    <h1>{{ $report['facility_label'] }} — Attendance Report</h1>
    <p class="meta">{{ $report['from'] }} to {{ $report['to'] }}</p>

    <table class="summary">
        <tr><th>Total visits</th><td>{{ $report['summary']['total_visits'] }}</td></tr>
        <tr><th>Walk-in visits</th><td>{{ $report['summary']['walk_in_visits'] }}</td></tr>
        <tr><th>Hotel guest visits</th><td>{{ $report['summary']['hotel_guest_visits'] }}</td></tr>
        <tr><th>Total collected</th><td>{{ number_format($report['summary']['total_collected'], 0) }} {{ config('nexstay.currency.default', 'TZS') }}</td></tr>
    </table>

    <h2>Visit log</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Visitor</th>
                <th>Type</th>
                <th>Settlement</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['attendances'] as $row)
                <tr>
                    <td>{{ $row->attended_at->format('d M Y H:i') }}</td>
                    <td>{{ $row->visitor_name }}</td>
                    <td>{{ $row->reservation_id ? 'Hotel guest' : 'Walk-in' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->settlement)) }}</td>
                    <td>{{ number_format((float) $row->amount, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
