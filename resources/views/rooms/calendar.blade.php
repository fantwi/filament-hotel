<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-7 max-w-2xl"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Plan your stay</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $roomType->name }} availability</h1><p class="mt-3 text-sm leading-6 text-gray-600 sm:text-base">Use the calendar to view available dates and begin a reservation.</p></div>
            <div class="mb-6 flex flex-wrap gap-3 text-sm font-medium"><span class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-2 text-green-800"><i class="h-3 w-3 rounded-full bg-green-500"></i>Available</span><span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-2 text-red-800"><i class="h-3 w-3 rounded-full bg-red-500"></i>Booked</span><span class="inline-flex items-center gap-2 rounded-full bg-yellow-50 px-3 py-2 text-yellow-800"><i class="h-3 w-3 rounded-full bg-yellow-400"></i>Limited</span></div>
            <div class="overflow-x-auto rounded-2xl border border-gray-100 bg-white p-3 shadow-xl shadow-slate-200/70 sm:p-6"><div id="availability-calendar" class="min-w-[640px]"></div></div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('availability-calendar');
            const calendar = new Calendar(calendarEl, {
                plugins: [dayGridPlugin, interactionPlugin],
                initialView: 'dayGridMonth',
                height: 'auto',
                events: @json($events),
                dateClick: function (info) { window.location.href = '/booking/details?date=' + info.dateStr; },
            });
            calendar.render();
        });
    </script>
</x-guest-layout>
