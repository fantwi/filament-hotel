<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservation #{{ $reservation->id }}</title>
</head>
<body onload="window.print()">
    <h1>Restaurant Reservation</h1>

    <p><strong>Reservation #:</strong> {{ $reservation->id }}</p>
    <p><strong>Guest:</strong> {{ $reservation->guest_name }}</p>
    <p><strong>Restaurant:</strong> {{ $reservation->restaurant->name }}</p>
    <p><strong>Table:</strong> {{ $reservation->table->table_number }}</p>
    <p><strong>Date:</strong> {{ $reservation->reservation_date->toFormattedDateString() }}</p>
    <p><strong>Time:</strong> {{ $reservation->reservation_time->format('H:i') }}</p>
</body>
</html>
