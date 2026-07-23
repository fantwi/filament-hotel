<h2>Restaurant Reservation Reminder</h2>

<p>Hello {{ $reservation->guest_name }}, this is a reminder of your reservation tomorrow at {{ $reservation->restaurant->name }}.</p>

<p>Table {{ $reservation->table->table_number }}, {{ $reservation->reservation_time->format('H:i') }}.</p>
