<h2>Hello {{ $reservation->guest_name }},</h2>

<p>Your reservation at {{ $reservation->restaurant->name }} has been confirmed.</p>

<p>Table {{ $reservation->table->table_number }} is reserved for {{ $reservation->reservation_date->toFormattedDateString() }} at {{ $reservation->reservation_time->format('H:i') }}.</p>
