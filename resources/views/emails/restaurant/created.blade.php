<h2>Hello {{ $reservation->guest_name }},</h2>

<p>Thank you for choosing {{ $reservation->restaurant->name }}.</p>

<p>Your reservation has been received. Please complete payment within 15 minutes.</p>

<ul>
    <li>Table: {{ $reservation->table->table_number }}</li>
    <li>Date: {{ $reservation->reservation_date->toFormattedDateString() }}</li>
    <li>Time: {{ $reservation->reservation_time->format('H:i') }}</li>
</ul>

<p>Thank you.</p>
