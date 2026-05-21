<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Ticket - {{ $reservation->booking_ref }}</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #0f172a;
            background: #f8fafc;
        }

        .ticket {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .header {
            padding: 18px 24px;
            background: #0f172a;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .meta {
            margin: 6px 0 0;
            font-size: 12px;
            opacity: 0.85;
        }

        .body {
            padding: 18px 24px 24px;
        }

        .section {
            margin-top: 18px;
        }

        .section:first-child {
            margin-top: 0;
        }

        .section-title {
            margin: 0 0 8px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            font-weight: 700;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 16px;
        }

        .row {
            font-size: 13px;
            line-height: 1.5;
        }

        .label {
            color: #64748b;
            margin-right: 6px;
        }

        .value {
            font-weight: 600;
            color: #0f172a;
        }

        .notes {
            margin-top: 8px;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: 13px;
            white-space: pre-wrap;
        }

        .footer {
            margin-top: 20px;
            padding-top: 14px;
            border-top: 1px dashed #cbd5e1;
            font-size: 12px;
            color: #64748b;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .ticket {
                border: 0;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <article class="ticket">
        <header class="header">
            <div>
                <h1 class="title">{{ config('app.name') }} Reservation Ticket</h1>
                <p class="meta">Booking Ref: {{ $reservation->booking_ref }}</p>
            </div>
            <div>
                <p class="meta">Generated: {{ $generatedAt->format('d M Y H:i') }}</p>
                <p class="meta">Status: {{ str_replace('_', ' ', strtoupper((string) $reservation->status)) }}</p>
            </div>
        </header>

        <div class="body">
            <section class="section">
                <h2 class="section-title">Guest Details</h2>
                <div class="grid">
                    <div class="row"><span class="label">Name:</span><span class="value">{{ trim(($reservation->guest?->first_name ?? '').' '.($reservation->guest?->last_name ?? '')) ?: 'N/A' }}</span></div>
                    <div class="row"><span class="label">Phone:</span><span class="value">{{ $reservation->guest?->phone ?: 'N/A' }}</span></div>
                    <div class="row"><span class="label">Email:</span><span class="value">{{ $reservation->guest?->email ?: 'N/A' }}</span></div>
                    <div class="row"><span class="label">Nationality:</span><span class="value">{{ $reservation->guest?->nationality ?: 'N/A' }}</span></div>
                    <div class="row"><span class="label">ID Type:</span><span class="value">{{ $reservation->guest?->id_type ?: 'N/A' }}</span></div>
                    <div class="row"><span class="label">ID Number:</span><span class="value">{{ $reservation->guest?->id_number ?: 'N/A' }}</span></div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Booking Details</h2>
                <div class="grid">
                    <div class="row"><span class="label">Check-in:</span><span class="value">{{ $reservation->check_in_date?->format('d M Y') ?: 'N/A' }}</span></div>
                    <div class="row"><span class="label">Check-out:</span><span class="value">{{ $reservation->check_out_date?->format('d M Y') ?: 'N/A' }}</span></div>
                    <div class="row"><span class="label">Nights:</span><span class="value">{{ $reservation->total_nights }}</span></div>
                    <div class="row"><span class="label">Guests:</span><span class="value">{{ $reservation->adults }} adult(s), {{ $reservation->children }} child(ren)</span></div>
                    <div class="row"><span class="label">Room:</span><span class="value">{{ $reservation->room?->room_number ? 'Room '.$reservation->room->room_number : 'Unassigned' }}</span></div>
                    <div class="row"><span class="label">Room Type:</span><span class="value">{{ $reservation->roomType?->name ?: $reservation->room?->roomType?->name ?: 'N/A' }}</span></div>
                    <div class="row"><span class="label">Rate Plan:</span><span class="value">{{ $reservation->ratePlan?->name ?: 'N/A' }}</span></div>
                    <div class="row"><span class="label">Payment Mode:</span><span class="value">{{ strtoupper((string) $paymentMode) }}</span></div>
                    <div class="row"><span class="label">Source:</span><span class="value">{{ $reservation->source ?: 'Direct' }}</span></div>
                    <div class="row"><span class="label">OTA Ref:</span><span class="value">{{ $reservation->ota_ref ?: 'N/A' }}</span></div>
                </div>
                @if ($reservation->special_requests)
                    <div class="notes">{{ $reservation->special_requests }}</div>
                @endif
            </section>

            <section class="section">
                <h2 class="section-title">Financial Summary</h2>
                <div class="grid">
                    <div class="row"><span class="label">Daily Rate:</span><span class="value">@money($reservation->daily_rate)</span></div>
                    <div class="row"><span class="label">Total Stay Amount:</span><span class="value">@money($reservation->total_amount)</span></div>
                    <div class="row"><span class="label">Deposit:</span><span class="value">@money($reservation->deposit_amount ?? 0)</span></div>
                    @if ($reservation->status === 'cancelled')
                        <div class="row"><span class="label">Cancellation Charge:</span><span class="value">@money($reservation->cancellation_charge_amount ?? 0)</span></div>
                        <div class="row"><span class="label">Refund Amount:</span><span class="value">@money($reservation->cancellation_refund_amount ?? 0)</span></div>
                    @endif
                </div>
            </section>

            <footer class="footer">
                Keep this ticket for check-in and any booking support.
            </footer>
        </div>
    </article>
</body>
</html>
