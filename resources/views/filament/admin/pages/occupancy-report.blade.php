<x-filament::page>
    @php
        $report = $this->report();
    @endphp
    <div class="space-y-6">
        <x-filament::section>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Operations</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">Occupancy and capacity</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Compare live inventory availability with hotel stays, conference bookings, and table reservations scheduled for one reporting period.</p>
            </div>
            <x-filament.report-period-controls class="mt-5" />
        </x-filament::section>

        <section
            aria-label="Occupancy overview"
            wire:loading.class="opacity-60"
            wire:target="applyReportPeriod,resetReportPeriod"
        >
            @livewire(\App\Filament\Admin\Widgets\OccupancyStats::class, ['period' => $this->period, 'startDate' => $this->startDate, 'endDate' => $this->endDate], key('occupancy-stats-'.$this->period.'-'.$this->startDate.'-'.$this->endDate))
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <x-filament::section heading="Room inventory" description="Live status across {{ number_format($report['roomStatus']['total']) }} room(s)">
                <dl class="space-y-3">
                    @foreach ([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
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

            <x-filament::section heading="Scheduled use" description="{{ $this->periodLabel() }} reservations and bookings">
                <dl class="space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-gray-600 dark:text-gray-300">Hotel stays</dt>
                        <dd class="font-semibold">{{ number_format($report['hotelBookings']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-gray-600 dark:text-gray-300">Conference bookings</dt>
                        <dd class="font-semibold">{{ number_format($report['conferenceBookings']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-gray-600 dark:text-gray-300">Table reservations</dt>
                        <dd class="font-semibold">{{ number_format($report['tableReservations']) }}</dd>
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
        </section>

        <x-filament::section heading="Report notes">
            <div class="grid gap-4 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2 lg:grid-cols-3">
                <p><span class="font-semibold text-gray-900 dark:text-white">Room occupancy</span> measures active hotel bookings overlapping the selected period, excluding cancelled, expired, and no-show stays.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Room-night capacity</span> uses total inventory for completed historical dates and today's in-service inventory for current or future dates, because past maintenance intervals are not stored.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Live availability</span> combines operational status with bookings active at the current time. Future bookings remain visible in scheduled use without reducing availability now.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
