<h2>Payment Successful</h2>

<p>Your payment has been received for restaurant reservation #{{ $reservation->id }}.</p>

<p>Amount paid: GHS {{ number_format($reservation->reservation_fee, 2) }}</p>
