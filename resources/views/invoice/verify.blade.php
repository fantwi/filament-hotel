<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice Verification</title>
    <style>body{margin:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a}body>*{max-width:640px;margin-left:auto;margin-right:auto}h2{margin-top:0;padding-top:24px}@media(max-width:640px){body{padding:20px;box-sizing:border-box;background:#fff}}</style>
</head>

<body>

<h2>Invoice Verification</h2>

<p><strong>Invoice:</strong> {{ $booking->invoice_number }}</p>
<p><strong>Guest:</strong> {{ $booking->guest->full_name }}</p>
<p><strong>Room:</strong> {{ $booking->room->room_number }}</p>
<p><strong>Check In:</strong> {{ $booking->check_in }}</p>
<p><strong>Check Out:</strong> {{ $booking->check_out }}</p>

<p><strong>Total Paid:</strong> GHS {{ number_format($booking->total_paid,2) }}</p>

</body>
</html>
