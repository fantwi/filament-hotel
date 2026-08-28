<x-filament::page>
    @vite('resources/js/app.js')

    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 px-5 py-6 text-white shadow-sm sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-100">Reservations</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Booking calendar</h1>
                    <p class="mt-2 text-sm leading-6 text-primary-100 sm:text-base">See hotel stays, conference bookings, and restaurant reservations together. Select an event to open its record.</p>
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
                        <li class="flex gap-2"><span class="text-primary-500">•</span><span>Hover over an event for guest and timing details.</span></li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>

    <script>
        (() => {
            const initializeBookingCalendar = () => {
                const element = document.getElementById('booking-calendar');
                const status = document.getElementById('booking-calendar-status');
                const emptyState = document.getElementById('booking-calendar-empty');
                const errorState = document.getElementById('booking-calendar-error');

                if (! element || element.dataset.initialized === 'true') {
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
                        if (info.event.url) {
                            info.jsEvent.preventDefault();
                            window.location.assign(info.event.url);

                            return;
                        }

                        const { type, guest, status: eventStatus, details } = info.event.extendedProps;
                        window.alert([info.event.title, type, guest, eventStatus, details]
                            .filter(Boolean)
                            .join('\n'));
                    },
                    eventDidMount(info) {
                        const { type, guest, status: eventStatus, details } = info.event.extendedProps;
                        info.el.title = [type, guest, eventStatus, details].filter(Boolean).join(' — ');
                    },
                });

                calendar.render();
            };

            document.addEventListener('DOMContentLoaded', initializeBookingCalendar, { once: true });
            document.addEventListener('livewire:navigated', initializeBookingCalendar);
            queueMicrotask(initializeBookingCalendar);
        })();
    </script>
</x-filament::page>
