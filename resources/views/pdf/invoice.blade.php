<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hotel Invoice</title>

    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td, table th {
            border: 1px solid #ccc;
            padding: 8px;
        }

        .total {
            font-weight: bold;
        }
    </style>
</head>

<body>

@php
    $verifyUrl = url('/invoice/'.$booking->invoice_number);
@endphp

<div class="header">
    <div class="title">Hotel Invoice</div>

    <div style="margin-top:5px; font-size:14px;">
        Invoice Number: {{ $booking->invoice_number }}
    </div>
</div>

<p><strong>Booking ID:</strong> {{ $booking->id }}</p>
<p><strong>Guest:</strong> {{ $booking->guest->full_name }}</p>
<p><strong>Room:</strong> {{ $booking->room->room_number }}</p>

<p><strong>Check In:</strong> {{ \Carbon\Carbon::parse($booking->check_in)->format('d M Y') }}</p>
<p><strong>Check Out:</strong> {{ \Carbon\Carbon::parse($booking->check_out)->format('d M Y') }}</p>

<table>
    <thead>
        <tr>
            <th>Description</th>
            <th>Amount</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>Room Charge</td>
            <td>GHS {{ number_format($booking->total_price, 2) }}</td>
        </tr>

        <tr>
            <td>Total Paid</td>
            <td>GHS {{ number_format($booking->total_paid, 2) }}</td>
        </tr>

        <tr class="total">
            <td>Balance</td>
            <td>GHS {{ number_format($booking->balance, 2) }}</td>
        </tr>
    </tbody>
</table>

<div style="margin-top:20px; text-align:center;">
    <img src="data:image/png;base64,
    {{ base64_encode(QrCode::format('png')->size(120)->generate($verifyUrl)) }}">
</div>

<div>
    Scan to verify invoice
</div>

<hr style="margin-top:40px;">

<div style="border-top:1px solid #ccc; padding-top:10px; text-align:center; font-size:11px; color:#666;">
    This invoice is computer generated and verified via QR code.<br>
    {{ config('app.name') }} | Generated on {{ now()->format('d M Y H:i') }}
</div>

<!-- <div style="text-align:center; font-size:12px; color:#777; margin-top:10px;">
    This invoice is computer generated and verified via QR code.
</div> -->

</body>
</html>
