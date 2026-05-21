<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexStay Daily Report</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; margin: 0; padding: 24px; background: #f8fafc;">
    <div style="max-width: 720px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
        <h1 style="margin: 0 0 8px; font-size: 22px;">Daily report summary</h1>
        <p style="margin: 0 0 18px; color: #6b7280;">Date: {{ $reportDate }}</p>

        <h2 style="font-size: 16px; margin: 0 0 8px;">Room reservations finance</h2>
        <ul style="margin: 0 0 18px; padding-left: 18px;">
            <li>Total reservations: {{ $roomReservationReport['total_reservations'] }}</li>
            <li>Room nights: {{ $roomReservationReport['room_nights'] }}</li>
            <li>Projected room revenue: {{ $roomReservationReport['projected_room_revenue'] }}</li>
            <li>Deposits collected: {{ $roomReservationReport['deposits_collected'] }}</li>
            <li>Balance expected on arrival: {{ $roomReservationReport['balance_expected_on_arrival'] }}</li>
        </ul>

        <h2 style="font-size: 16px; margin: 0 0 8px;">Room payments and accounting</h2>
        <ul style="margin: 0 0 18px; padding-left: 18px;">
            <li>Total reservations: {{ $roomAccountingReport['totals']['reservations'] }}</li>
            <li>Room nights: {{ $roomAccountingReport['totals']['room_nights'] }}</li>
            <li>Room revenue: {{ $roomAccountingReport['totals']['room_revenue'] }}</li>
            <li>Payments received: {{ $roomAccountingReport['totals']['payments_received'] }}</li>
            <li>Outstanding balance: {{ $roomAccountingReport['totals']['outstanding_balance'] }}</li>
        </ul>

        <h2 style="font-size: 16px; margin: 0 0 8px;">Food and beverage</h2>
        <ul style="margin: 0 0 18px; padding-left: 18px;">
            <li>Food revenue: {{ $fbRevenueReport['food'] }}</li>
            <li>Drinks revenue: {{ $fbRevenueReport['drinks'] }}</li>
            <li>Total F&B revenue: {{ $fbRevenueReport['total'] }}</li>
        </ul>

        <h2 style="font-size: 16px; margin: 0 0 8px;">Outstanding debts</h2>
        <ul style="margin: 0; padding-left: 18px;">
            <li>Open folios count: {{ $outstandingDebtsCount }}</li>
            <li>Total outstanding: {{ $outstandingDebtsTotal }}</li>
        </ul>
    </div>
</body>
</html>
