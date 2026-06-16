<h1>Hotel Booking Invoice</h1>

<p>
Booking ID:
{{ $booking->id }}
</p>

<p>
Guest:
{{ $booking->guest->name }}
</p>

<p>
Amount:
GHS {{ $booking->total_price }}
</p>

<p>
Status:
{{ $booking->status }}
</p>