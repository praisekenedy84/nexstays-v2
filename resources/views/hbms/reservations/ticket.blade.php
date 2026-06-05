<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Ticket &mdash; {{ $reservation->booking_ref }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            font-size: 11px;
            color: #0f172a;
            background: #f1f5f9;
            padding: 16px;
        }

        .receipt {
            max-width: 420px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        /* ── Header band ── */
        .receipt-header {
            background: #0f172a;
            color: #ffffff;
            padding: 16px 18px 12px;
            text-align: center;
        }

        .property-name {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .property-sub {
            font-size: 9px;
            opacity: 0.55;
            margin-top: 1px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .ref-band {
            margin-top: 10px;
            display: inline-block;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 5px;
            padding: 5px 14px;
        }

        .ref-label {
            font-size: 8px;
            opacity: 0.60;
            letter-spacing: 0.10em;
            text-transform: uppercase;
        }

        .ref-value {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        /* ── Status pill ── */
        .status-row { text-align: center; padding: 6px 0 3px; }

        .status-pill {
            display: inline-block;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.10em;
            text-transform: uppercase;
            border-radius: 999px;
            padding: 2px 9px;
        }

        .status-confirmed   { background: #dcfce7; color: #15803d; }
        .status-checked_in  { background: #dbeafe; color: #1d4ed8; }
        .status-checked_out { background: #f1f5f9; color: #475569; }
        .status-cancelled   { background: #fee2e2; color: #b91c1c; }
        .status-inquiry     { background: #fef9c3; color: #854d0e; }
        .status-no_show     { background: #fde8d8; color: #9a3412; }

        /* ── Tear line ── */
        .tear { border: none; border-top: 1px dashed #cbd5e1; margin: 0 14px; }

        /* ── Dates block ── */
        .dates-row { padding: 10px 16px 8px; }

        .dates-table { width: 100%; border-collapse: collapse; }

        .dates-table td { text-align: center; vertical-align: middle; padding: 0 3px; }

        .date-label {
            font-size: 8px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .date-value { font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 1px; }
        .date-year  { font-size: 9px; color: #64748b; }

        .dates-divider { font-size: 16px; color: #cbd5e1; font-weight: 300; padding: 0 3px; }

        .nights-box { text-align: center; }
        .nights-num { font-size: 18px; font-weight: 800; color: #0f172a; }
        .nights-lbl { font-size: 8px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.07em; }

        /* ── Room highlight ── */
        .room-block {
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            padding: 8px 16px;
            text-align: center;
        }

        .room-number    { font-size: 18px; font-weight: 800; color: #0f172a; }
        .room-type      { font-size: 10px; color: #475569; margin-top: 1px; }
        .room-unassigned { font-size: 11px; font-weight: 600; color: #94a3b8; }

        /* ── Detail rows ── */
        .detail-section { padding: 9px 16px 3px; }

        .section-heading {
            font-size: 8px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.10em;
            margin-bottom: 5px;
        }

        .detail-row { width: 100%; margin-bottom: 3px; }

        .detail-row td.dl {
            font-size: 10px;
            color: #64748b;
            width: 42%;
            vertical-align: top;
        }

        .detail-row td.dv {
            font-size: 10px;
            font-weight: 600;
            color: #0f172a;
            vertical-align: top;
        }

        /* ── Financial block ── */
        .finance-block {
            margin: 3px 16px 0;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }

        .finance-row { width: 100%; border-collapse: collapse; }

        .finance-row td {
            padding: 5px 10px;
            font-size: 10px;
            vertical-align: middle;
        }

        .finance-row tr + tr td { border-top: 1px solid #f1f5f9; }

        .finance-label { color: #64748b; }
        .finance-value { text-align: right; font-weight: 600; color: #0f172a; }

        .finance-total-row td {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 11px;
        }

        .finance-total-row td.finance-value { color: #ffffff; }

        /* ── Notes ── */
        .notes-block {
            margin: 7px 16px 0;
            padding: 6px 9px;
            background: #fef9c3;
            border-radius: 5px;
            font-size: 10px;
            color: #713f12;
            line-height: 1.5;
        }

        /* ── QR block ── */
        .qr-block { padding: 10px 16px 8px; text-align: center; }
        .qr-block img { width: 72px; height: 72px; }
        .qr-hint {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 3px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-weight: 500;
        }

        /* ── Footer ── */
        .receipt-footer {
            background: #f8fafc;
            border-top: 1px dashed #cbd5e1;
            padding: 8px 16px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }

        /* ── Print ── */
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: #ffffff; padding: 0; }
            .receipt { border: 0; border-radius: 0; max-width: 100%; box-shadow: none; page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<article class="receipt">

    {{-- ── Header ── --}}
    <header class="receipt-header">
        <div class="property-name">{{ config('app.name') }}</div>
        <div class="property-sub">Reservation Ticket</div>
        <div style="margin-top:10px;">
            <div class="ref-band">
                <div class="ref-label">Booking Reference</div>
                <div class="ref-value">{{ $reservation->booking_ref }}</div>
            </div>
        </div>
    </header>

    {{-- ── Status ── --}}
    <div class="status-row">
        <span class="status-pill status-{{ $reservation->status }}">
            {{ str_replace('_', ' ', $reservation->status) }}
        </span>
    </div>

    <hr class="tear">

    {{-- ── Dates ── --}}
    <div class="dates-row">
        <table class="dates-table">
            <tr>
                <td>
                    <div class="date-label">Check-in</div>
                    <div class="date-value">{{ $reservation->check_in_date->format('d M') }}</div>
                    <div class="date-year">{{ $reservation->check_in_date->format('Y') }}</div>
                </td>
                <td class="dates-divider">&rarr;</td>
                <td>
                    <div class="date-label">Check-out</div>
                    <div class="date-value">{{ $reservation->check_out_date->format('d M') }}</div>
                    <div class="date-year">{{ $reservation->check_out_date->format('Y') }}</div>
                </td>
                <td style="width:1px; background:#e2e8f0; padding:0;"></td>
                <td class="nights-box">
                    <div class="nights-num">{{ $reservation->total_nights }}</div>
                    <div class="nights-lbl">Night{{ $reservation->total_nights !== 1 ? 's' : '' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Room ── --}}
    <div class="room-block">
        @if ($reservation->room?->room_number)
            <div class="room-number">Room {{ $reservation->room->room_number }}</div>
            <div class="room-type">{{ $reservation->roomType?->name ?? $reservation->room?->roomType?->name ?? '' }}</div>
        @else
            <div class="room-unassigned">Room TBA</div>
            <div class="room-type">{{ $reservation->roomType?->name ?? '' }}</div>
        @endif
    </div>

    {{-- ── Guest ── --}}
    <div class="detail-section">
        <div class="section-heading">Guest</div>
        <table class="detail-row">
            <tr>
                <td class="dl">Name</td>
                <td class="dv">{{ trim(($reservation->guest?->first_name ?? '').' '.($reservation->guest?->last_name ?? '')) ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="dl">Phone</td>
                <td class="dv">{{ $reservation->guest?->phone ?: 'N/A' }}</td>
            </tr>
            @if ($reservation->guest?->email)
            <tr>
                <td class="dl">Email</td>
                <td class="dv">{{ $reservation->guest->email }}</td>
            </tr>
            @endif
            @if ($reservation->guest?->nationality)
            <tr>
                <td class="dl">Nationality</td>
                <td class="dv">{{ $reservation->guest->nationality }}</td>
            </tr>
            @endif
            @if ($reservation->guest?->id_number)
            <tr>
                <td class="dl">{{ $reservation->guest?->id_type ?: 'ID' }}</td>
                <td class="dv">{{ $reservation->guest->id_number }}</td>
            </tr>
            @endif
            <tr>
                <td class="dl">Guests</td>
                <td class="dv">{{ $reservation->adults }} adult{{ $reservation->adults !== 1 ? 's' : '' }}{{ $reservation->children ? ', '.$reservation->children.' child'.($reservation->children !== 1 ? 'ren' : '') : '' }}</td>
            </tr>
        </table>
    </div>

    {{-- ── Booking meta ── --}}
    <div class="detail-section" style="padding-top:6px;">
        <div class="section-heading">Booking</div>
        <table class="detail-row">
            <tr>
                <td class="dl">Rate plan</td>
                <td class="dv">{{ $reservation->ratePlan?->name ?: 'Standard' }}</td>
            </tr>
            <tr>
                <td class="dl">Payment mode</td>
                <td class="dv">{{ strtoupper((string) $paymentMode) }}</td>
            </tr>
            @if ($reservation->source)
            <tr>
                <td class="dl">Payment source</td>
                <td class="dv">{{ app(\App\Domain\Shared\Services\PaymentMethodSettingsService::class)->labelFor($reservation->source) }}</td>
            </tr>
            @endif
            <tr>
                <td class="dl">Booked by</td>
                <td class="dv">{{ $reservation->creator?->name ?? '—' }}</td>
            </tr>
            @if ($reservation->ota_ref)
            <tr>
                <td class="dl">OTA Ref</td>
                <td class="dv">{{ $reservation->ota_ref }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- ── Financials ── --}}
    <div class="detail-section" style="padding-bottom:10px;">
        <div class="section-heading" style="margin-bottom:5px;">Financials</div>
        <div class="finance-block">
            <table class="finance-row" style="width:100%; border-collapse:collapse;">
                <tr>
                    <td class="finance-label">Daily rate</td>
                    <td class="finance-value">@money($reservation->daily_rate)</td>
                </tr>
                <tr>
                    <td class="finance-label">{{ $reservation->total_nights }} night{{ $reservation->total_nights !== 1 ? 's' : '' }}</td>
                    <td class="finance-value">@money($reservation->total_amount)</td>
                </tr>
                @if ($reservation->deposit_amount > 0)
                <tr>
                    <td class="finance-label">Deposit</td>
                    <td class="finance-value">@money($reservation->deposit_amount)</td>
                </tr>
                @endif
                @if ($reservation->status === 'cancelled')
                <tr>
                    <td class="finance-label">Cancellation charge</td>
                    <td class="finance-value">@money($reservation->cancellation_charge_amount ?? 0)</td>
                </tr>
                <tr>
                    <td class="finance-label">Refund</td>
                    <td class="finance-value">@money($reservation->cancellation_refund_amount ?? 0)</td>
                </tr>
                @endif
                <tr class="finance-total-row">
                    <td class="finance-label">Total stay</td>
                    <td class="finance-value">@money($reservation->total_amount)</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ── Special requests ── --}}
    @if ($reservation->special_requests)
        <div class="notes-block">
            <strong>Special requests:</strong> {{ $reservation->special_requests }}
        </div>
        <div style="height:7px;"></div>
    @endif

    <hr class="tear">

    {{-- ── QR code ── --}}
    <div class="qr-block">
        <img src="data:image/svg+xml;base64,{{ $qrSvgB64 }}"
             width="72" height="72" alt="QR: {{ $reservation->booking_ref }}">
        <div class="qr-hint">{{ $reservation->booking_ref }}</div>
    </div>

    {{-- ── Footer ── --}}
    <footer class="receipt-footer">
        Generated {{ $generatedAt->format('d M Y, H:i') }} &nbsp;&middot;&nbsp;
        Keep this ticket for check-in and booking support.
    </footer>

</article>

</body>
</html>
