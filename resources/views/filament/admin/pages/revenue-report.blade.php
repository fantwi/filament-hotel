<x-filament::page>
    @php
        $report = $this->report();
    @endphp
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Finance</p>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight">Revenue performance</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Track funds received, refunds, outstanding balances, and payment-method performance for one consistent reporting period.</p>
                </div>

                <div class="w-full lg:max-w-xs">
                    <label for="revenue-report-period" class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Report period</label>
                    <select id="revenue-report-period" wire:model.live="period" class="fi-input w-full">
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

        <section aria-label="Revenue overview">
            @livewire(\App\Filament\Admin\Widgets\RevenueReportStats::class, ['period' => $this->period], key('revenue-report-stats-'.$this->period))
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <x-filament::section heading="Outstanding by transaction" description="{{ $this->periodLabel() }} unpaid balances">
                <dl class="space-y-3">
                    @foreach ([
                        'hotel' => 'Hotel bookings',
                        'conference' => 'Conference bookings',
                        'table' => 'Table reservations',
                        'food' => 'Food orders',
                    ] as $key => $label)
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-sm text-gray-600 dark:text-gray-300">{{ $label }}</dt>
                            <dd class="font-semibold">GHS {{ number_format($report['outstandingBreakdown'][$key], 2) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-filament::section>

            <x-filament::section heading="Payment methods" description="Completed payments received during {{ strtolower($this->periodLabel()) }}" class="lg:col-span-2">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($report['methods'] as $method)
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                            <p class="text-sm font-semibold capitalize">{{ str_replace('_', ' ', $method->method ?: 'Unknown') }}</p>
                            <p class="mt-2 text-xl font-bold">GHS {{ number_format($method->total, 2) }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($method->payment_count) }} payment(s)</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No completed payments were recorded for {{ strtolower($this->periodLabel()) }}.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </section>

        <x-filament::section heading="Report notes">
            <div class="grid gap-4 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                <p><span class="font-semibold text-gray-900 dark:text-white">Revenue received</span> includes payments marked paid or completed on their recorded payment date.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Outstanding balance</span> includes unpaid hotel, conference, table-reservation, and food-order transactions created in the selected period.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
