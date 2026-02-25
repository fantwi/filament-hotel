<!-- <x-filament-panels::page>
    {{-- Page content --}}
</x-filament-panels::page> -->

<x-filament-panels::page>
    <div id="calendar"></div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {

                var calendarEl = document.getElementById('calendar');

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    events: '/admin/calendar-events',
                });

                calendar.render();
            });
        </script>
    @endpush
</x-filament-panels::page>