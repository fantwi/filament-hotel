<x-filament::page>
    <div x-data="bookingCalendar()" x-init="initCalendar()">
        <div id="calendar"></div>

        <div
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 bg-black/50 z-40"
            @click="open = false"
        ></div>

        <div
            x-show="open"
            x-transition
            class="fixed inset-0 flex items-center justify-center z-50"
        >
            <div
                class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 border border-gray-200"
                @click.stop
            >
                <h2 class="text-lg font-semibold mb-4">
                    Booking Details
                </h2>

                <div class="space-y-2 text-sm">
                    <p><strong>Guest:</strong> <span x-text="booking.title"></span></p>
                    <p><strong>Room:</strong> <span x-text="booking.room"></span></p>
                    <p><strong>Check-in:</strong> <span x-text="booking.check_in"></span></p>
                    <p><strong>Check-out:</strong> <span x-text="booking.check_out"></span></p>
                    <p><strong>Total:</strong> GHS <span x-text="booking.total_price"></span></p>
                    <p><strong>Balance:</strong> GHS <span x-text="booking.balance"></span></p>

                    <p>
                        <strong>Status:</strong>
                        <span
                            class="px-2 py-1 rounded text-white text-xs"
                            :class="{
                                'bg-warning-500': booking.status === 'pending',
                                'bg-primary-500': booking.status === 'checked_in',
                                'bg-success-600': booking.status === 'checked_out'
                            }"
                            x-text="booking.status"
                        ></span>
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <a
                        :href="'/admin/bookings/' + booking.booking_id + '/edit'"
                        class="filament-button filament-button-color-primary"
                    >
                        Edit Booking
                    </a>

                    <button
                        @click="open = false"
                        class="filament-button filament-button-color-gray"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <script>
    function bookingCalendar() {
        return {
            open: false,
            booking: {},

            initCalendar() {
                const calendar = new FullCalendar.Calendar(
                    document.getElementById('calendar'),
                    {
                        initialView: 'dayGridMonth',
                        events: '/admin/calendar-events',
                        editable: true,
                        eventDurationEditable: true,

                        eventDrop: (info) => {
                            this.handleReschedule(info);
                        },

                        eventResize: (info) => {
                            this.handleReschedule(info);
                        },
                    }
                );

                calendar.render();
            },

            handleReschedule(info) {
                const bookingId = info.event.extendedProps.booking_id;
                const newStart = info.event.startStr;
                let newEnd = info.event.end;

                if (newEnd) {
                    newEnd = new Date(newEnd);
                    newEnd.setDate(newEnd.getDate() - 1);
                    newEnd = newEnd.toISOString().split('T')[0];
                }

                fetch(`/admin/bookings/${bookingId}/reschedule`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        check_in: newStart,
                        check_out: newEnd,
                    })
                })
                    .then(async response => {
                        const data = await response.json().catch(() => ({
                            success: false,
                            message: 'Error updating booking.',
                        }));

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Error updating booking.');
                        }

                        return data;
                    })
                    .catch(error => {
                        alert(error.message || 'Error updating booking.');
                        info.revert();
                    });
            }
        }
    }
    </script>
</x-filament::page>
