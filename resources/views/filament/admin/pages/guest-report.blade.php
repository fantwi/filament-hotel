<x-filament::page>
    @php
        $report = $this->report();
    @endphp
    <div class="space-y-6">
        <x-filament::section>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Guest insights</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">Guest performance</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Monitor guest growth, paying-guest activity, repeat visits, and spend for one consistent reporting period.</p>
            </div>
            <x-filament.report-period-controls class="mt-5" />
        </x-filament::section>

        <section aria-label="Guest overview">
            @livewire(\App\Filament\Admin\Widgets\GuestStats::class, [
                'reportData' => [
                    'totalGuests' => $report['totalGuests'],
                    'newGuests' => $report['newGuests'],
                    'payingGuests' => $report['payingGuests'],
                    'returningGuests' => $report['returningGuests'],
                    'averageSpend' => $report['averageSpend'],
                ],
                'reportPeriodLabel' => $this->periodLabel(),
            ], key('guest-stats-'.$this->period.'-'.$this->startDate.'-'.$this->endDate))
        </section>

        <x-filament::section heading="Guest spending" description="Collections and refund events recorded during {{ strtolower($this->periodLabel()) }}">
            <dl class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-500/20 dark:bg-primary-500/10">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Gross guest spend</dt>
                    <dd class="mt-1 text-2xl font-bold tabular-nums text-primary-700 dark:text-primary-300">GHS {{ number_format($report['totalPaid'], 2) }}</dd>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Payments received in the selected period</p>
                </div>
                <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-500/20 dark:bg-danger-500/10">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Refunds processed</dt>
                    <dd class="mt-1 text-2xl font-bold tabular-nums text-danger-700 dark:text-danger-300">GHS {{ number_format($report['refundTotal'], 2) }}</dd>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($report['refundCount']) }} refund event(s) in the selected period</p>
                </div>
                <div @class([
                    'rounded-xl border p-4',
                    'border-success-200 bg-success-50 dark:border-success-500/20 dark:bg-success-500/10' => $report['netSpend'] > 0,
                    'border-danger-200 bg-danger-50 dark:border-danger-500/20 dark:bg-danger-500/10' => $report['netSpend'] < 0,
                    'border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5' => $report['netSpend'] === 0.0,
                ])>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Net guest spend</dt>
                    <dd @class([
                        'mt-1 text-2xl font-bold tabular-nums',
                        'text-success-700 dark:text-success-300' => $report['netSpend'] > 0,
                        'text-danger-700 dark:text-danger-300' => $report['netSpend'] < 0,
                        'text-gray-900 dark:text-white' => $report['netSpend'] === 0.0,
                    ])>GHS {{ number_format($report['netSpend'], 2) }}</dd>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Gross collections less refunds processed</p>
                </div>
            </dl>
        </x-filament::section>

        <section class="grid gap-4 lg:grid-cols-3">
            <x-filament::section heading="Guest activity" description="{{ $this->periodLabel() }} paid transaction activity">
                <div class="space-y-4">
                    <div class="rounded-xl bg-primary-50 p-4 dark:bg-primary-500/10">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Returning guests</p>
                        <p class="mt-1 text-2xl font-bold text-primary-700 dark:text-primary-300">{{ number_format($report['returningGuests']) }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Two or more distinct paid service visits in range</p>
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

            <x-filament::section heading="Top guests by gross spend" description="Gross collections recorded during {{ strtolower($this->periodLabel()) }}" class="lg:col-span-2">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <caption class="sr-only">Top guests by gross spend</caption>
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-3 py-3 font-semibold">Guest</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Email</th>
                                <th scope="col" class="px-3 py-3 text-right font-semibold">Payments</th>
                                <th scope="col" class="px-3 py-3 text-right font-semibold">Gross spend</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($report['topGuests'] as $payment)
                                <tr>
                                    <th scope="row" class="px-3 py-3 font-semibold">{{ $payment->guest?->full_name ?? 'Guest not recorded' }}</th>
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
                <p><span class="font-semibold text-gray-900 dark:text-white">Paying guests</span> are distinct guests whose payments were received in the selected period, including collections refunded later.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Guest spend</span> records gross collections by payment date and refunds by processing date; unlinked transactions are excluded.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
