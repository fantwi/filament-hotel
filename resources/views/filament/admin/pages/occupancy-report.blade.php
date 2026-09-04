<x-filament::page>
    @php
        $report = $this->report();
        $statsSummary = [
            'occupancyRate' => $report['occupancyRate'],
            'bookedRoomNights' => $report['bookedRoomNights'],
            'roomNightCapacity' => $report['roomNightCapacity'],
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
                ],
                key('occupancy-stats-'.$this->period.'-'.$this->startDate.'-'.$this->endDate)
            )

            <x-filament::section heading="Scheduled use" description="{{ $this->periodLabel() }} reservations and bookings">
                <dl class="grid gap-3 sm:grid-cols-3">
                    <div class="flex items-center justify-between gap-4 rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <dt class="text-sm text-gray-600 dark:text-gray-300">Hotel stays</dt>
                        <dd class="font-semibold">{{ number_format($report['hotelBookings']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <dt class="text-sm text-gray-600 dark:text-gray-300">Conference bookings</dt>
                        <dd class="font-semibold">{{ number_format($report['conferenceBookings']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <dt class="text-sm text-gray-600 dark:text-gray-300">Table reservations</dt>
                        <dd class="font-semibold">{{ number_format($report['tableReservations']) }}</dd>
                    </div>
                </dl>
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
