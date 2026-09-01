<x-filament::page>
    @vite('resources/js/calendar.js')

    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 px-5 py-6 text-white shadow-sm sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-100">Reservations</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">All reservation channels</h2>
                    <p class="mt-2 text-sm leading-6 text-primary-100 sm:text-base">Select an event to review its guest, timing, and status before opening the full record.</p>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center text-xs sm:gap-3 sm:text-sm">
                    <div class="rounded-xl bg-white/10 px-3 py-3 ring-1 ring-white/15">
                        <x-filament::icon icon="heroicon-o-building-office-2" class="mx-auto h-5 w-5 text-primary-100" />
                        <span class="mt-1 block">Hotel stays</span>
                    </div>
                    <div class="rounded-xl bg-white/10 px-3 py-3 ring-1 ring-white/15">
                        <x-filament::icon icon="heroicon-o-presentation-chart-bar" class="mx-auto h-5 w-5 text-primary-100" />
                        <span class="mt-1 block">Conferences</span>
                    </div>
                    <div class="rounded-xl bg-white/10 px-3 py-3 ring-1 ring-white/15">
                        <x-filament::icon icon="heroicon-o-cake" class="mx-auto h-5 w-5 text-primary-100" />
                        <span class="mt-1 block">Tables</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <section class="min-w-0 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 sm:p-5" wire:ignore>
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-4 dark:border-white/10">
                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Reservation schedule</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use the arrows to move between periods.</p>
                    </div>
                    <span id="booking-calendar-status" class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300" role="status">Loading calendar…</span>
                </div>

                <div id="booking-calendar" class="min-h-[32rem]" aria-label="Booking calendar"></div>
                <div id="booking-calendar-empty" class="hidden rounded-xl border border-dashed border-gray-300 px-5 py-12 text-center dark:border-gray-700">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="mx-auto h-10 w-10 text-gray-400" />
                    <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">No reservations in this range</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try another month or week to see scheduled activity.</p>
                </div>
                <div id="booking-calendar-error" class="hidden rounded-xl border border-dashed border-danger-300 px-5 py-12 text-center dark:border-danger-700">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mx-auto h-10 w-10 text-danger-500" />
                    <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Calendar unavailable</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Refresh the page or contact an administrator if the problem continues.</p>
                </div>
            </section>

            <aside class="space-y-4 lg:sticky lg:top-6" aria-label="Calendar guide">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                            <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="font-semibold text-gray-950 dark:text-white">Calendar guide</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Status colours at a glance</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3 text-sm">
                        <div class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-warning-500"></span><span class="text-gray-700 dark:text-gray-300">Pending</span></div>
                        <div class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-success-500"></span><span class="text-gray-700 dark:text-gray-300">Confirmed</span></div>
                        <div class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-primary-500"></span><span class="text-gray-700 dark:text-gray-300">Checked in</span></div>
                        <div class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-gray-500"></span><span class="text-gray-700 dark:text-gray-300">Completed / other</span></div>
                        <div class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-danger-500"></span><span class="text-gray-700 dark:text-gray-300">Cancelled</span></div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-white/10 dark:bg-gray-800/50">
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Helpful shortcuts</h2>
                    <ul class="mt-3 space-y-3 text-sm leading-5 text-gray-600 dark:text-gray-300">
                        <li class="flex gap-2"><span class="text-primary-500">•</span><span>Switch to week view when checking daily capacity.</span></li>
                        <li class="flex gap-2"><span class="text-primary-500">•</span><span>Click an event to open the related reservation.</span></li>
                        <li class="flex gap-2"><span class="text-primary-500">•</span><span>Open an event for guest, status, and timing details.</span></li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>

    <div
        x-data="{ selectedEvent: null }"
        x-on:booking-calendar-event-selected.window="selectedEvent = $event.detail; $dispatch('open-modal', { id: 'booking-calendar-event-modal' })"
    >
        <x-filament::modal
            id="booking-calendar-event-modal"
            heading="Reservation details"
            description="Review the reservation before opening its record."
            width="lg"
            slide-over
        >
            <div x-cloak x-show="selectedEvent" class="space-y-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary-600 dark:text-primary-400" x-text="selectedEvent ? selectedEvent.type : 'Reservation'"></p>
                    <h3 class="mt-2 text-xl font-semibold text-gray-950 dark:text-white" x-text="selectedEvent ? selectedEvent.title : 'Reservation'"></h3>
                </div>

                <dl class="grid gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-white/10 dark:bg-gray-800/60 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Guest</dt>
                        <dd class="mt-1 font-medium text-gray-950 dark:text-white" x-text="selectedEvent ? selectedEvent.guest : '—'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="mt-1 font-medium capitalize text-gray-950 dark:text-white" x-text="selectedEvent ? selectedEvent.status : '—'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment</dt>
                        <dd class="mt-1 font-medium capitalize text-gray-950 dark:text-white" x-text="selectedEvent ? selectedEvent.paymentStatus : '—'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Starts</dt>
                        <dd class="mt-1 font-medium text-gray-950 dark:text-white" x-text="selectedEvent ? selectedEvent.start : '—'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Ends</dt>
                        <dd class="mt-1 font-medium text-gray-950 dark:text-white" x-text="selectedEvent ? selectedEvent.end : '—'"></dd>
                    </div>
                </dl>

                <div x-show="selectedEvent && selectedEvent.details" class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Additional details</p>
                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-300" x-text="selectedEvent ? selectedEvent.details : ''"></p>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 dark:border-white/10 sm:flex-row sm:items-center">
                    <a
                        x-show="selectedEvent && selectedEvent.url"
                        x-bind:href="selectedEvent && selectedEvent.url ? selectedEvent.url : '#'"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:w-auto"
                    >
                        Open reservation record
                    </a>
                    <p x-show="selectedEvent && ! selectedEvent.url" class="text-sm text-gray-500 dark:text-gray-400">
                        No direct record link is available for this reservation.
                    </p>
                </div>
            </div>
        </x-filament::modal>
    </div>

    <script>
        (() => {
            const controller = window.__bookingCalendarController ??= {};

            const formatEventDate = (value, allDay = false) => {
                if (! value) {
                    return '—';
                }

                if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                    const [year, month, day] = value.split('-').map(Number);

                    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(
                        new Date(year, month - 1, day),
                    );
                }

                const date = new Date(value);

                if (Number.isNaN(date.valueOf())) {
                    return value;
                }

                return new Intl.DateTimeFormat(undefined, allDay
                    ? { dateStyle: 'medium' }
                    : { dateStyle: 'medium', timeStyle: 'short' }).format(date);
            };

            const humanizeStatus = (status) => status
                ? status.replace(/_/g, ' ')
                    .replace(/\b\w/g, (character) => character.toUpperCase())
                : 'Unknown';

            const eventDetails = (event) => {
                const { type, guest, status, payment_status: paymentStatus, details } = event.extendedProps ?? {};

                return {
                    id: event.id,
                    title: event.title,
                    type: type ?? 'Reservation',
                    guest: guest ?? 'Unknown guest',
                    status: humanizeStatus(status),
                    paymentStatus: paymentStatus ? humanizeStatus(paymentStatus) : 'Not recorded',
                    details: details ?? '',
                    start: formatEventDate(event.startStr, event.allDay),
                    end: formatEventDate(event.endStr, event.allDay),
                    url: event.url || null,
                };
            };

            const openEventDetails = (event, browserEvent = null) => {
                browserEvent?.preventDefault();
                window.dispatchEvent(new CustomEvent('booking-calendar-event-selected', {
                    detail: eventDetails(event),
                }));
            };

            const destroyBookingCalendar = () => {
                const element = document.getElementById('booking-calendar');

                if (! element?.__bookingCalendar) {
                    return;
                }

                element.__bookingCalendar.destroy();
                delete element.__bookingCalendar;
                delete element.dataset.initialized;
            };

            const initializeBookingCalendar = () => {
                const element = document.getElementById('booking-calendar');
                const status = document.getElementById('booking-calendar-status');
                const emptyState = document.getElementById('booking-calendar-empty');
                const errorState = document.getElementById('booking-calendar-error');

                if (! element || element.__bookingCalendar) {
                    return;
                }

                const showState = (state, message = null) => {
                    emptyState?.classList.toggle('hidden', state !== 'empty');
                    errorState?.classList.toggle('hidden', state !== 'error');
                    element.classList.toggle('hidden', state === 'empty' || state === 'error');

                    if (status) {
                        status.textContent = message ?? (state === 'empty' ? 'No reservations' : 'Live schedule');
                    }
                };

                if (typeof window.Calendar !== 'function' || ! window.dayGridPlugin || ! window.interactionPlugin) {
                    showState('error', 'Calendar library unavailable');
                    return;
                }

                element.dataset.initialized = 'true';

                const calendar = new window.Calendar(element, {
                    plugins: [window.dayGridPlugin, window.interactionPlugin],
                    initialView: window.innerWidth < 640 ? 'dayGridWeek' : 'dayGridMonth',
                    height: 'auto',
                    expandRows: true,
                    dayMaxEvents: true,
                    fixedWeekCount: false,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek',
                    },
                    buttonText: {
                        today: 'Today',
                        month: 'Month',
                        week: 'Week',
                    },
                    events: {
                        url: @js(route('admin.calendar-events')),
                        failure: () => showState('error', 'Unable to load reservations'),
                        success: (events) => {
                            showState(events.length ? 'ready' : 'empty');
                            return events;
                        },
                    },
                    eventClick(info) {
                        openEventDetails(info.event, info.jsEvent);
                    },
                    eventDidMount(info) {
                        const details = eventDetails(info.event);
                        const label = [details.title, details.type, details.guest, details.status, details.paymentStatus, details.start, details.end]
                            .filter(Boolean)
                            .join(', ');

                        info.el.removeAttribute('title');
                        info.el.setAttribute('aria-label', label);
                        info.el.setAttribute('aria-haspopup', 'dialog');
                        info.el.setAttribute('role', 'button');
                        info.el.setAttribute('tabindex', '0');
                        info.el.addEventListener('keydown', (event) => {
                            if (event.key === 'Enter' || event.key === ' ') {
                                openEventDetails(info.event, event);
                            }
                        });
                    },
                });

                element.__bookingCalendar = calendar;
                calendar.render();
            };

            controller.initialize = initializeBookingCalendar;
            controller.destroy = destroyBookingCalendar;

            if (! controller.listenersRegistered) {
                document.addEventListener('DOMContentLoaded', initializeBookingCalendar, { once: true });
                document.addEventListener('livewire:navigating', destroyBookingCalendar);
                document.addEventListener('livewire:navigated', initializeBookingCalendar);
                controller.listenersRegistered = true;
            }

            queueMicrotask(initializeBookingCalendar);
        })();
    </script>
</x-filament::page>
