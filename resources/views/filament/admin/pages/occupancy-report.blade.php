<x-filament::page>
    @php
        $report = $this->report();
        $statsSummary = [
            'occupancyRate' => $report['occupancyRate'],
            'bookedRoomNights' => $report['bookedRoomNights'],
            'roomNightCapacity' => $report['roomNightCapacity'],
        ];
        $scheduledUseUrls = [
            'hotel' => $this->reservationCalendarUrl('hotel'),
            'conference' => $this->reservationCalendarUrl('conference'),
            'restaurant' => $this->reservationCalendarUrl('restaurant'),
        ];
    @endphp
    <div class="space-y-6">
        <x-filament::section>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Operations</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">Occupancy and capacity</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Review selected-period performance separately from the hotel's current operational availability.</p>
            </div>
            <x-filament.report-period-controls class="mt-5" />
        </x-filament::section>

        <section
            aria-labelledby="selected-period-heading"
            class="space-y-4"
            wire:loading.class="opacity-60"
            wire:target="applyReportPeriod,resetReportPeriod"
        >
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Reporting range</p>
                <h2 id="selected-period-heading" class="mt-1 text-xl font-bold tracking-tight text-gray-950 dark:text-white">Selected period performance</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Occupancy and scheduled activity for {{ $this->periodLabel() }} ({{ $report['periodStart']->toDateString() }} to {{ $report['periodEnd']->toDateString() }}).</p>
            </div>

            @livewire(
                \App\Filament\Admin\Widgets\OccupancyStats::class,
                [
                    'summary' => $statsSummary,
                    'periodLabel' => $this->periodLabel(),
                    'hotelBookingsUrl' => $scheduledUseUrls['hotel'],
                ],
                key('occupancy-stats-'.$this->period.'-'.$this->startDate.'-'.$this->endDate)
            )

            <x-filament::section heading="Scheduled use" description="{{ $this->periodLabel() }} reservations and bookings">
                <ul aria-label="Scheduled use metrics" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <li class="min-w-0 rounded-xl border border-primary-200 bg-primary-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-primary-500/20 dark:bg-primary-500/10">
                        <a href="{{ $scheduledUseUrls['hotel'] }}" aria-label="View hotel stays for the selected period" class="group block rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                            <p class="flex items-center gap-2 text-sm font-semibold text-primary-800 dark:text-primary-200">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-500/20 dark:text-primary-300">
                                    <x-filament::icon icon="heroicon-o-building-office-2" class="h-5 w-5" />
                                </span>
                                Hotel stays
                            </p>
                            <p class="mt-3 break-words text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ number_format($report['hotelBookings']) }}</p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Bookings overlapping the selected range</p>
                            <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-primary-700 dark:text-primary-300">
                                View schedule
                                <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </a>
                    </li>
                    <li class="min-w-0 rounded-xl border border-info-200 bg-info-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-info-500/20 dark:bg-info-500/10">
                        <a href="{{ $scheduledUseUrls['conference'] }}" aria-label="View conference bookings for the selected period" class="group block rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                            <p class="flex items-center gap-2 text-sm font-semibold text-info-800 dark:text-info-200">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-info-100 text-info-600 dark:bg-info-500/20 dark:text-info-300">
                                    <x-filament::icon icon="heroicon-o-presentation-chart-bar" class="h-5 w-5" />
                                </span>
                                Conference bookings
                            </p>
                            <p class="mt-3 break-words text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ number_format($report['conferenceBookings']) }}</p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Venue bookings scheduled in the range</p>
                            <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-info-700 dark:text-info-300">
                                View schedule
                                <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </a>
                    </li>
                    <li class="min-w-0 rounded-xl border border-warning-200 bg-warning-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-warning-500/20 dark:bg-warning-500/10">
                        <a href="{{ $scheduledUseUrls['restaurant'] }}" aria-label="View table reservations for the selected period" class="group block rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                            <p class="flex items-center gap-2 text-sm font-semibold text-warning-800 dark:text-warning-200">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-warning-100 text-warning-600 dark:bg-warning-500/20 dark:text-warning-300">
                                    <x-filament::icon icon="heroicon-o-cake" class="h-5 w-5" />
                                </span>
                                Table reservations
                            </p>
                            <p class="mt-3 break-words text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ number_format($report['tableReservations']) }}</p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Dining reservations scheduled in the range</p>
                            <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-warning-700 dark:text-warning-300">
                                View schedule
                                <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </a>
                    </li>
                </ul>
            </x-filament::section>
        </section>

        <section aria-labelledby="live-snapshot-heading" class="space-y-4 border-t border-gray-200 pt-6 dark:border-white/10">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-warning-600 dark:text-warning-400">Current operations</p>
                    <h2 id="live-snapshot-heading" class="mt-1 text-xl font-bold tracking-tight text-gray-950 dark:text-white">Live operational snapshot</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Current inventory and venue availability; these values do not follow the selected report period.</p>
                </div>
                <time datetime="{{ $report['snapshotAt']->toIso8601String() }}" class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    As of {{ $report['snapshotAt']->format('M j, Y g:i A T') }}
                </time>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <x-filament::section heading="Room inventory" description="Live status across {{ number_format($report['roomStatus']['total']) }} room(s)">
                    <dl class="space-y-3">
                        @foreach ([
                            'available' => 'Available now',
                            'occupied' => 'Occupied now',
                            'maintenance' => 'Under maintenance',
                        ] as $key => $label)
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">{{ $label }}</dt>
                                <dd class="font-semibold">{{ number_format($report['roomStatus'][$key]) }}</dd>
                            </div>
                        @endforeach
                        <div class="border-t border-gray-200 pt-3 dark:border-white/10">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-sm font-semibold text-gray-900 dark:text-white">Rooms in service</dt>
                                <dd class="font-semibold">{{ number_format($report['roomsInService']) }}</dd>
                            </div>
                        </div>
                    </dl>
                </x-filament::section>

                <x-filament::section heading="Venue availability" description="Live conference and dining capacity">
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-sm text-gray-600 dark:text-gray-300">Conference rooms available now</dt>
                            <dd class="font-semibold">{{ number_format($report['conferenceAvailability']['available']) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-sm text-gray-600 dark:text-gray-300">Conference rooms unavailable now</dt>
                            <dd class="font-semibold">{{ number_format($report['conferenceAvailability']['unavailable']) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-gray-200 pt-3 dark:border-white/10">
                            <dt class="text-sm text-gray-600 dark:text-gray-300">Tables available now</dt>
                            <dd class="font-semibold">{{ number_format($report['tableStatus']['available']) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-sm text-gray-600 dark:text-gray-300">Tables reserved or occupied now</dt>
                            <dd class="font-semibold">{{ number_format($report['tableStatus']['reserved'] + $report['tableStatus']['occupied']) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-sm text-gray-600 dark:text-gray-300">Tables cleaning or under maintenance</dt>
                            <dd class="font-semibold">{{ number_format($report['tableStatus']['unavailable']) }}</dd>
                        </div>
                    </dl>
                </x-filament::section>
            </div>
        </section>

        <x-filament::section heading="Report notes">
            <div class="grid gap-4 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2 lg:grid-cols-3">
                <p><span class="font-semibold text-gray-900 dark:text-white">Room occupancy</span> measures active hotel bookings overlapping the selected period, excluding cancelled, expired, and no-show stays.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Room-night capacity</span> uses total inventory for completed historical dates and today's in-service inventory for current or future dates, because past maintenance intervals are not stored. Occupancy is shown as N/A when this capacity is zero.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Live availability</span> combines operational status with bookings active at the current time. Future bookings remain visible in scheduled use without reducing availability now.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
