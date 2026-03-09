<x-filament::page>

<div class="overflow-x-auto">

<table class="w-full border text-sm">

<thead class="bg-gray-100 dark:bg-gray-800">
<tr>
<th class="p-3 border">Room</th>

@for ($i = 0; $i < 14; $i++)
<th class="p-3 border">
{{ \Carbon\Carbon::today()->addDays($i)->format('M d') }}
</th>
@endfor

</tr>
</thead>

<tbody>

@foreach(\App\Models\Room::with('bookings')->get() as $room)

<tr>

<td class="p-3 border font-semibold">
Room {{ $room->room_number }}
</td>

@for ($i = 0; $i < 14; $i++)

@php
$date = \Carbon\Carbon::today()->addDays($i)->toDateString();

$booking = $room->bookings
    ->where('check_in', '<=', $date)
    ->where('check_out', '>', $date)
    ->first();
@endphp

<td class="border text-center p-2">

@if ($booking)

<div class="bg-primary-500 text-white text-xs rounded p-1">
{{ $booking->guest->first_name }}
</div>

@endif

</td>

@endfor

</tr>

@endforeach

</tbody>

</table>

</div>

<div id="timeline"></div>

<!-- FullCalendar Scheduler -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const calendar = new FullCalendar.Calendar(
        document.getElementById('timeline'),
        {
            initialView: 'resourceTimelineWeek',
            resources: '/admin/timeline-rooms',
            events: '/admin/timeline-bookings',
        }
    );

    calendar.render();

});

</script>

</x-filament::page>