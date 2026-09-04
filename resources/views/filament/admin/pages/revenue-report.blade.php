<x-filament::page>
    @php
        $report = $this->report();
        $revenueChannels = [
            'hotel' => [
                'label' => 'Hotel bookings',
                'description' => 'Room stay payments',
                'icon' => 'heroicon-o-building-office-2',
                'classes' => 'border-primary-200 bg-primary-50/60 dark:border-primary-800 dark:bg-primary-950/20',
                'iconClasses' => 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300',
            ],
            'conference' => [
                'label' => 'Conference bookings',
                'description' => 'Venue payments',
                'icon' => 'heroicon-o-presentation-chart-bar',
                'classes' => 'border-info-200 bg-info-50/60 dark:border-info-800 dark:bg-info-950/20',
                'iconClasses' => 'bg-info-100 text-info-700 dark:bg-info-900/50 dark:text-info-300',
            ],
            'table' => [
                'label' => 'Table reservations',
                'description' => 'Dining reservation payments',
                'icon' => 'heroicon-o-calendar-days',
                'classes' => 'border-warning-200 bg-warning-50/60 dark:border-warning-800 dark:bg-warning-950/20',
                'iconClasses' => 'bg-warning-100 text-warning-700 dark:bg-warning-900/50 dark:text-warning-300',
            ],
            'food' => [
                'label' => 'Food orders',
                'description' => 'Restaurant order payments',
                'icon' => 'heroicon-o-cake',
                'classes' => 'border-success-200 bg-success-50/60 dark:border-success-800 dark:bg-success-950/20',
                'iconClasses' => 'bg-success-100 text-success-700 dark:bg-success-900/50 dark:text-success-300',
            ],
            'other' => [
                'label' => 'Other / direct',
                'description' => 'Unlinked and direct payments',
                'icon' => 'heroicon-o-receipt-percent',
                'classes' => 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-white/5',
                'iconClasses' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
            ],
        ];
    @endphp
    <div class="space-y-6">
        <x-filament::section>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Finance</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">Revenue performance</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Track funds received, refunds, outstanding balances, and payment-method performance for one consistent reporting period.</p>
            </div>
            <x-filament.report-period-controls class="mt-5" />
        </x-filament::section>

        <section aria-label="Revenue overview">
            @livewire(\App\Filament\Admin\Widgets\RevenueReportStats::class, [
                'reportData' => [
                    'revenue' => $report['revenue'],
                    'paymentsReceived' => $report['paymentsReceived'],
                    'refunds' => $report['refunds'],
                    'refundCount' => $report['refundCount'],
                    'netRevenue' => $report['netRevenue'],
                    'outstanding' => $report['outstanding'],
                ],
                'reportPeriodLabel' => $this->periodLabel(),
            ], key('revenue-report-stats-'.$this->period.'-'.$this->startDate.'-'.$this->endDate))
        </section>

        <x-filament::section aria-labelledby="revenue-channel-heading">
            <x-slot name="heading">
                <span id="revenue-channel-heading">Revenue by business channel</span>
            </x-slot>
            <x-slot name="description">Collected payments received during {{ strtolower($this->periodLabel()) }}</x-slot>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ($revenueChannels as $key => $presentation)
                    @php
                        $channel = $report['revenueByChannel'][$key];
                        $share = $report['revenue'] > 0
                            ? ($channel['total'] / $report['revenue']) * 100
                            : 0;
                    @endphp
                    <article class="min-w-0 rounded-xl border p-4 {{ $presentation['classes'] }}">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $presentation['iconClasses'] }}">
                                <x-filament::icon :icon="$presentation['icon']" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $presentation['label'] }}</h3>
                                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">{{ $presentation['description'] }}</p>
                            </div>
                        </div>
                        <p class="mt-4 truncate text-xl font-bold tabular-nums text-gray-950 dark:text-white" title="GHS {{ number_format($channel['total'], 2) }}">
                            GHS {{ number_format($channel['total'], 2) }}
                        </p>
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-600 dark:text-gray-300">
                            <span>{{ number_format($channel['payment_count']) }} payment(s)</span>
                            <span class="font-semibold tabular-nums">{{ number_format($share, 1) }}% of revenue</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </x-filament::section>

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
