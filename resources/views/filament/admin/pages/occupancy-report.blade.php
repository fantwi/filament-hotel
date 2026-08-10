<x-filament::page>
    @php
        $report = $this->report();
    @endphp
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Operations</p>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight">Occupancy and capacity</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Compare live inventory availability with hotel stays, conference bookings, and table reservations scheduled for one reporting period.</p>
                </div>

                <div class="w-full lg:max-w-xs">
                    <label for="occupancy-report-period" class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Report period</label>
                    <select id="occupancy-report-period" wire:model.live="period" class="fi-input w-full">
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                        <option value="this_month">This Month</option>
                        <option value="this_quarter">This Quarter</option>
                        <option value="this_year">This Year</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
            </div>
        </x-filament::section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Room occupancy</p>
                <p class="mt-2 text-3xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($report['occupancyRate'], 1) }}%</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Booked room nights in {{ strtolower($this->periodLabel()) }}</p>
            </x-filament::section>

            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Booked room nights</p>
                <p class="mt-2 text-3xl font-bold text-success-600 dark:text-success-400">{{ number_format($report['bookedRoomNights']) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Of {{ number_format($report['roomNightCapacity']) }} available room nights</p>
            </x-filament::section>

            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rooms available now</p>
                <p class="mt-2 text-3xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($report['roomStatus']['available']) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Live room inventory status</p>
            </x-filament::section>

            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tables available now</p>
                <p class="mt-2 text-3xl font-bold text-warning-600 dark:text-warning-400">{{ number_format($report['tableStatus']['available']) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Live restaurant table status</p>
            </x-filament::section>
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
                        <dt class="text-sm text-gray-600 dark:text-gray-300">Conference rooms available</dt>
                        <dd class="font-semibold">{{ number_format($report['conferenceAvailability']['available']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-gray-600 dark:text-gray-300">Conference rooms unavailable</dt>
                        <dd class="font-semibold">{{ number_format($report['conferenceAvailability']['unavailable']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-gray-600 dark:text-gray-300">Tables reserved or occupied</dt>
                        <dd class="font-semibold">{{ number_format($report['tableStatus']['reserved'] + $report['tableStatus']['occupied']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-gray-600 dark:text-gray-300">Tables unavailable</dt>
                        <dd class="font-semibold">{{ number_format($report['tableStatus']['unavailable']) }}</dd>
                    </div>
                </dl>
            </x-filament::section>
        </section>

        <x-filament::section heading="Report notes">
            <div class="grid gap-4 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                <p><span class="font-semibold text-gray-900 dark:text-white">Room occupancy</span> measures active hotel bookings overlapping the selected period, excluding cancelled, expired, and no-show stays.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Live availability</span> reflects each room, conference room, and table's current operational status and is separate from future scheduled activity.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
