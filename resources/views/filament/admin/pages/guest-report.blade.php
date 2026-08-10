<x-filament::page>
    @php
        $report = $this->report();
    @endphp
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Guest insights</p>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight">Guest performance</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Monitor guest growth, paying-guest activity, repeat visits, and spend for one consistent reporting period.</p>
                </div>

                <div class="w-full lg:max-w-xs">
                    <label for="guest-report-period" class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Report period</label>
                    <select id="guest-report-period" wire:model.live="period" class="fi-input w-full">
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
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Guest profiles</p>
                <p class="mt-2 text-3xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($report['totalGuests']) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">All registered guest records</p>
            </x-filament::section>

            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">New guests</p>
                <p class="mt-2 text-3xl font-bold text-success-600 dark:text-success-400">{{ number_format($report['newGuests']) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Profiles created during {{ strtolower($this->periodLabel()) }}</p>
            </x-filament::section>

            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paying guests</p>
                <p class="mt-2 text-3xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($report['payingGuests']) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Guests with a paid transaction</p>
            </x-filament::section>

            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average guest spend</p>
                <p class="mt-2 text-3xl font-bold text-warning-600 dark:text-warning-400">GHS {{ number_format($report['averageSpend'], 2) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Average paid spend per guest</p>
            </x-filament::section>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <x-filament::section heading="Guest activity" description="{{ $this->periodLabel() }} paid transaction activity">
                <div class="space-y-4">
                    <div class="rounded-xl bg-primary-50 p-4 dark:bg-primary-500/10">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Returning guests</p>
                        <p class="mt-1 text-2xl font-bold text-primary-700 dark:text-primary-300">{{ number_format($report['returningGuests']) }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Two or more paid transactions in range</p>
                    </div>

                    <dl class="space-y-3">
                        @foreach ([
                            'hotel' => 'Hotel bookings',
                            'conference' => 'Conference bookings',
                            'table' => 'Table reservations',
                            'food' => 'Food orders',
                        ] as $key => $label)
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">{{ $label }}</dt>
                                <dd class="font-semibold">{{ number_format($report['activity'][$key]) }} payment(s)</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </x-filament::section>

            <x-filament::section heading="Top guests by spend" description="Paid spend recorded during {{ strtolower($this->periodLabel()) }}" class="lg:col-span-2">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-3 font-semibold">Guest</th>
                                <th class="px-3 py-3 font-semibold">Email</th>
                                <th class="px-3 py-3 text-right font-semibold">Payments</th>
                                <th class="px-3 py-3 text-right font-semibold">Paid spend</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($report['topGuests'] as $payment)
                                <tr>
                                    <td class="px-3 py-3 font-semibold">{{ $payment->guest?->full_name ?? 'Guest not recorded' }}</td>
                                    <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $payment->guest?->email ?? '-' }}</td>
                                    <td class="px-3 py-3 text-right">{{ number_format($payment->payment_count) }}</td>
                                    <td class="px-3 py-3 text-right font-semibold">GHS {{ number_format($payment->total_spend, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">No paid guest activity was recorded for {{ strtolower($this->periodLabel()) }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </section>

        <x-filament::section heading="Report notes">
            <div class="grid gap-4 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                <p><span class="font-semibold text-gray-900 dark:text-white">Paying guests</span> are distinct guests with payments marked paid or completed in the selected period.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Guest spend</span> uses completed payment amounts only; unpaid, refunded, and unlinked transactions are excluded.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
