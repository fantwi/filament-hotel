<x-filament::page>
    @vite('resources/js/app.js')

    <div class="mb-6 flex flex-wrap gap-4 text-sm">
        <span><span class="text-amber-500">●</span> Pending</span>
        <span><span class="text-green-500">●</span> Confirmed</span>
        <span><span class="text-blue-500">●</span> Checked in</span>
        <span><span class="text-red-500">●</span> Cancelled</span>
        <span><span class="text-gray-500">●</span> Completed / other</span>
    </div>

    <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-900" wire:ignore>
        <div id="booking-calendar"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const element = document.getElementById('booking-calendar');

            if (! element || element.dataset.initialized) {
                return;
            }

            element.dataset.initialized = 'true';

            const calendar = new window.Calendar(element, {
                plugins: [window.dayGridPlugin, window.interactionPlugin],
                initialView: 'dayGridMonth',
                height: 'auto',
                events: '{{ route('admin.calendar-events') }}',
                eventClick(info) {
                    if (info.event.url) {
                        info.jsEvent.preventDefault();
                        window.location.assign(info.event.url);

                        return;
                    }

                    const { type, guest, status, details } = info.event.extendedProps;
                    window.alert([info.event.title, type, guest, status, details]
                        .filter(Boolean)
                        .join('\n'));
                },
                eventDidMount(info) {
                    const { type, guest, status, details } = info.event.extendedProps;
                    info.el.title = [type, guest, status, details].filter(Boolean).join(' — ');
                },
            });

            calendar.render();
        });
    </script>
</x-filament::page>
