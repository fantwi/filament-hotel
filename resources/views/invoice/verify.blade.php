<!DOCTYPE html>
<html>
<head>
    <title>Invoice Verification</title>
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