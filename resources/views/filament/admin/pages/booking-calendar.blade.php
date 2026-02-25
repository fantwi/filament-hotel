<x-filament::page>
    <div x-data="bookingCalendar()" x-init="initCalendar()">
        <div id="calendar"></div>

        <!-- Modal Backdrop -->
        <div
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 bg-black/50 z-40"
            @click="open = false"
        ></div>

        <!-- Modal -->
        <div
            x-show="open"
            x-transition
            class="fixed inset-0 flex items-center justify-center z-50"
        >
            <div
                class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700"
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

                            eventClick: (info) => {
                                this.booking = {
                                    title: info.event.title,
                                    room: info.event.extendedProps.room,
                                    check_in: info.event.extendedProps.check_in,
                                    check_out: info.event.extendedProps.check_out,
                                    total_price: info.event.extendedProps.total_price,
                                    balance: info.event.extendedProps.balance,
                                    status: info.event.extendedProps.status,
                                    booking_id: info.event.extendedProps.booking_id,
                                };

                                this.open = true;
                            }
                        }
                    );

                    calendar.render();
                }
            }
        }
    </script>
</x-filament::page>