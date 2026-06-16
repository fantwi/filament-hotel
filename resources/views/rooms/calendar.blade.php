<x-guest-layout>

<div class="max-w-7xl mx-auto py-12 px-4">

    <!-- HEADER -->
    <div class="mb-8">

        <h1 class="text-4xl font-bold">

            {{ $roomType->name }} Availability

        </h1>

        <p class="text-gray-500 mt-2">

            View available and booked dates

        </p>

    </div>

    <!-- LEGEND -->
    <div class="flex gap-6 mb-8">

        <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-green-500 rounded"></div>
            <span>Available</span>
        </div>

        <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-red-500 rounded"></div>
            <span>Booked</span>
        </div>

        <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-yellow-400 rounded"></div>
            <span>Limited</span>
        </div>

    </div>

    <!-- CALENDAR -->
    <div class="bg-white rounded-2xl shadow-sm border p-6">

        <div id="availability-calendar"></div>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const calendarEl =
        document.getElementById(
            'availability-calendar'
        );

    const calendar =
        new Calendar(calendarEl, {

            plugins: [
                dayGridPlugin,
                interactionPlugin
            ],

            initialView: 'dayGridMonth',

            height: 'auto',

            events:
                @json($events),
                // eventDisplay: 'block',

            dateClick: function(info) {

                window.location.href =
                    '/booking/details?date=' +
                    info.dateStr;

            }

            // eventDidMount: function(info) {

            //     info.el.setAttribute(
            //         'title',
            //         info.event.title
            //     );

            // },

        });

    calendar.render();

});

</script>

</x-guest-layout>