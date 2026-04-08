<x-filament::page>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>

<div style="margin-bottom:10px;">
<select wire:model.live="roomTypeFilter">
<option value="">All Room Types</option>
@foreach(\App\Models\RoomType::all() as $type)
<option value="{{ $type->id }}">
{{ $type->name }}
</option>
@endforeach
</select>
</div>

<div style="margin-bottom:10px; font-size:14px">
    <span style="color:#22c55e">¦</span> Available
    <span style="color:#f59e0b; margin-left:15px">¦</span> Nearly Full
    <span style="color:#ef4444; margin-left:15px">¦</span> Fully Booked
</div>

<div id="calendar" style="overflow-x:auto;" wire:ignore></div>

<script>
let calendar;

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');

    calendar = new FullCalendar.Calendar(calendarEl, {
        schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
        initialView: 'resourceTimelineWeek',
        height: 700,
        resourceAreaHeaderContent: 'Rooms',
        resources: @json($this->getRooms()),
        events: @json($this->getCalendarEvents()),
        displayEventTime: false,
        editable: true,
        slotDuration: { days: 1 },
        slotLabelFormat: {
            weekday: 'short',
            day: 'numeric'
        },

        dateClick: function (info) {
            window.location.href =
                '/admin/bookings/create?check_in=' + info.dateStr +
                '&room_id=' + info.resource.id;
        },

        eventDrop: function (info) {
            fetch('/admin/bookings/' + info.event.id + '/timeline-update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    room_id: info.newResource?.id ?? info.event.getResources()?.[0]?.id,
                    check_in: info.event.startStr,
                    check_out: info.event.endStr,
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        info.revert();
                    }
                })
                .catch(() => info.revert());
        }
    });

    calendar.render();
});
</script>

<script>
Livewire.on('refreshCalendar', data => {
    calendar.removeAllEvents();
    calendar.removeAllResources();
    calendar.addResource(data.rooms);
    calendar.addEventSource(data.events);
});
</script>

</x-filament::page>
