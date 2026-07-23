<h2>Refund Confirmation</h2>

<p>Hello {{ $reservation->guest_name }}, your restaurant reservation payment has been marked as refunded.</p>

<p>Amount: GHS {{ number_format($reservation->reservation_fee, 2) }}</p>
