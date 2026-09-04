<x-filament::page>
    @php
        $report = $this->report();
        $drillDowns = $this->drillDownUrls(
            $report['methods']->pluck('method')->filter()->map(fn ($method) => (string) $method)->values()->all(),
        );
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
        $outstandingChannels = [
            'hotel' => [
                'label' => 'Hotel bookings',
                'description' => 'Unpaid room stays',
                'icon' => 'heroicon-o-building-office-2',
                'tone' => 'primary',
                'classes' => 'border-primary-200 bg-primary-50/60 dark:border-primary-800 dark:bg-primary-950/20',
                'iconClasses' => 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300',
                'barClasses' => 'bg-primary-500 dark:bg-primary-400',
            ],
            'conference' => [
                'label' => 'Conference bookings',
                'description' => 'Outstanding venue bookings',
                'icon' => 'heroicon-o-presentation-chart-bar',
                'tone' => 'info',
                'classes' => 'border-info-200 bg-info-50/60 dark:border-info-800 dark:bg-info-950/20',
                'iconClasses' => 'bg-info-100 text-info-700 dark:bg-info-900/50 dark:text-info-300',
                'barClasses' => 'bg-info-500 dark:bg-info-400',
            ],
            'table' => [
                'label' => 'Table reservations',
                'description' => 'Unpaid reservation fees',
                'icon' => 'heroicon-o-calendar-days',
                'tone' => 'warning',
                'classes' => 'border-warning-200 bg-warning-50/60 dark:border-warning-800 dark:bg-warning-950/20',
                'iconClasses' => 'bg-warning-100 text-warning-700 dark:bg-warning-900/50 dark:text-warning-300',
                'barClasses' => 'bg-warning-500 dark:bg-warning-400',
            ],
            'food' => [
                'label' => 'Food orders',
                'description' => 'Outstanding meal orders',
                'icon' => 'heroicon-o-cake',
                'tone' => 'success',
                'classes' => 'border-success-200 bg-success-50/60 dark:border-success-800 dark:bg-success-950/20',
                'iconClasses' => 'bg-success-100 text-success-700 dark:bg-success-900/50 dark:text-success-300',
                'barClasses' => 'bg-success-500 dark:bg-success-400',
            ],
        ];
        $paymentMethodPresentations = [
            'cash' => [
                'label' => 'Cash',
                'icon' => 'heroicon-o-banknotes',
                'tone' => 'emerald',
                'classes' => 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800 dark:bg-emerald-950/20',
                'iconClasses' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                'barClasses' => 'bg-emerald-500 dark:bg-emerald-400',
            ],
            'momo' => [
                'label' => 'Mobile money',
                'icon' => 'heroicon-o-device-phone-mobile',
                'tone' => 'amber',
                'classes' => 'border-amber-200 bg-amber-50/70 dark:border-amber-800 dark:bg-amber-950/20',
                'iconClasses' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                'barClasses' => 'bg-amber-500 dark:bg-amber-400',
            ],
            'card' => [
                'label' => 'Card',
                'icon' => 'heroicon-o-credit-card',
                'tone' => 'indigo',
                'classes' => 'border-indigo-200 bg-indigo-50/70 dark:border-indigo-800 dark:bg-indigo-950/20',
                'iconClasses' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300',
                'barClasses' => 'bg-indigo-500 dark:bg-indigo-400',
            ],
            'paystack' => [
                'label' => 'Paystack',
                'icon' => 'heroicon-o-shield-check',
                'tone' => 'sky',
                'classes' => 'border-sky-200 bg-sky-50/70 dark:border-sky-800 dark:bg-sky-950/20',
                'iconClasses' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300',
                'barClasses' => 'bg-sky-500 dark:bg-sky-400',
            ],
            'corporate_account' => [
                'label' => 'Corporate account',
                'icon' => 'heroicon-o-building-office',
                'tone' => 'violet',
                'classes' => 'border-violet-200 bg-violet-50/70 dark:border-violet-800 dark:bg-violet-950/20',
                'iconClasses' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300',
                'barClasses' => 'bg-violet-500 dark:bg-violet-400',
            ],
            'bank_transfer' => [
                'label' => 'Bank transfer',
                'icon' => 'heroicon-o-building-library',
                'tone' => 'rose',
                'classes' => 'border-rose-200 bg-rose-50/70 dark:border-rose-800 dark:bg-rose-950/20',
                'iconClasses' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
                'barClasses' => 'bg-rose-500 dark:bg-rose-400',
            ],
        ];
        $defaultPaymentMethodPresentation = [
            'label' => 'Unknown',
            'icon' => 'heroicon-o-receipt-percent',
            'tone' => 'neutral',
            'classes' => 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-white/5',
            'iconClasses' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
            'barClasses' => 'bg-gray-500 dark:bg-gray-400',
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

        <section
            aria-label="Selected period revenue results"
            class="relative"
            wire:loading.attr="aria-busy"
            wire:target="applyReportPeriod,resetReportPeriod"
        >
            <div
                data-revenue-period-results
                class="space-y-6 transition-opacity duration-200"
                wire:loading.class="pointer-events-none opacity-60"
                wire:target="applyReportPeriod,resetReportPeriod"
            >
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
                'drillDownUrls' => [
                    'revenue' => $drillDowns['revenue'],
                    'refunds' => $drillDowns['refunds'],
                    'netRevenue' => $drillDowns['netRevenue'],
                    'outstanding' => $drillDowns['outstanding'],
                ],
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
                    <a
                        href="{{ $drillDowns['channels'][$key] }}"
                        aria-label="View {{ $presentation['label'] }} revenue payments"
                        data-revenue-drill-down="channel"
                        class="block rounded-xl transition hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    >
                        <article class="h-full min-w-0 rounded-xl border p-4 shadow-sm transition hover:shadow-md {{ $presentation['classes'] }}">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $presentation['iconClasses'] }}">
                                    <x-filament::icon :icon="$presentation['icon']" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $presentation['label'] }}</h3>
                                        <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                                    </div>
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
                    </a>
                @endforeach
            </div>
        </x-filament::section>

        <section aria-label="Previous-period revenue comparison">
            @livewire(\App\Filament\Admin\Widgets\RevenueComparisonStats::class, [
                'comparison' => $report['comparison'],
                'drillDownUrls' => [
                    'revenue' => $drillDowns['revenue'],
                    'refunds' => $drillDowns['refunds'],
                    'netRevenue' => $drillDowns['netRevenue'],
                ],
            ], key('revenue-comparison-stats-'.$this->period.'-'.$this->startDate.'-'.$this->endDate))
        </section>

        <section aria-label="Revenue trend chart">
            @livewire(\App\Filament\Admin\Widgets\RevenueTrendChart::class, [
                'trend' => $report['trend'],
                'periodLabel' => $this->periodLabel(),
            ], key('revenue-trend-chart-'.$this->period.'-'.$this->startDate.'-'.$this->endDate))
        </section>

        <x-filament::section aria-labelledby="outstanding-breakdown-heading">
            <x-slot name="heading">
                <span id="outstanding-breakdown-heading">Outstanding by transaction</span>
            </x-slot>
            <x-slot name="description">{{ $this->periodLabel() }} unpaid balances</x-slot>

            <div data-outstanding-grid class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($outstandingChannels as $key => $presentation)
                    @php
                        $amount = (float) $report['outstandingBreakdown'][$key];
                        $share = $report['outstanding'] > 0
                            ? ($amount / $report['outstanding']) * 100
                            : 0;
                        $boundedShare = max(0, min(100, $share));
                    @endphp
                    <a
                        href="{{ $drillDowns['outstanding'] }}"
                        aria-label="Analyze {{ $presentation['label'] }} outstanding balance"
                        data-revenue-drill-down="outstanding"
                        class="block rounded-xl transition hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    >
                        <article
                            data-outstanding-card
                            data-outstanding-tone="{{ $presentation['tone'] }}"
                            class="h-full min-w-0 rounded-xl border p-4 shadow-sm transition hover:shadow-md {{ $presentation['classes'] }}"
                        >
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $presentation['iconClasses'] }}">
                                    <x-filament::icon :icon="$presentation['icon']" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $presentation['label'] }}</h3>
                                        <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">{{ $presentation['description'] }}</p>
                                </div>
                            </div>

                            <p class="mt-4 truncate text-xl font-bold tabular-nums text-gray-950 dark:text-white" title="GHS {{ number_format($amount, 2) }}">
                                GHS {{ number_format($amount, 2) }}
                            </p>
                            <p class="mt-1 text-xs font-semibold tabular-nums text-gray-600 dark:text-gray-300">
                                {{ number_format($share, 1) }}% of outstanding
                            </p>
                            <div
                                role="progressbar"
                                aria-label="{{ $presentation['label'] }} share of outstanding balance"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ round($boundedShare, 1) }}"
                                class="mt-3 h-2 overflow-hidden rounded-full bg-white/80 ring-1 ring-inset ring-gray-950/5 dark:bg-white/10 dark:ring-white/10"
                            >
                                <span class="block h-full rounded-full {{ $presentation['barClasses'] }}" style="width: {{ $boundedShare }}%"></span>
                            </div>
                        </article>
                    </a>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section aria-labelledby="payment-methods-heading">
            <x-slot name="heading">
                <span id="payment-methods-heading">Payment methods</span>
            </x-slot>
            <x-slot name="description">Completed payments received during {{ strtolower($this->periodLabel()) }}</x-slot>

            <div data-payment-method-grid class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($report['methods'] as $method)
                    @php
                        $methodKey = (string) ($method->method ?: '');
                        $presentation = $paymentMethodPresentations[$methodKey] ?? $defaultPaymentMethodPresentation;
                        $methodLabel = $methodKey !== '' && ! array_key_exists($methodKey, $paymentMethodPresentations)
                            ? ucfirst(str_replace('_', ' ', $methodKey))
                            : $presentation['label'];
                        $methodTotal = (float) $method->total;
                        $share = $report['revenue'] > 0
                            ? ($methodTotal / $report['revenue']) * 100
                            : 0;
                        $boundedShare = max(0, min(100, $share));
                    @endphp
                    <a
                        href="{{ $drillDowns['methods'][$methodKey] ?? $drillDowns['revenue'] }}"
                        aria-label="View {{ $methodLabel }} revenue payments"
                        data-revenue-drill-down="method"
                        data-payment-method-key="{{ $methodKey }}"
                        class="block rounded-xl transition hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    >
                        <article
                            data-payment-method-card
                            data-payment-method-tone="{{ $presentation['tone'] }}"
                            class="h-full min-w-0 rounded-xl border p-4 shadow-sm transition hover:shadow-md {{ $presentation['classes'] }}"
                        >
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $presentation['iconClasses'] }}">
                                    <x-filament::icon :icon="$presentation['icon']" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $methodLabel }}</h3>
                                        <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">{{ number_format($method->payment_count) }} payment(s)</p>
                                </div>
                            </div>

                            <p class="mt-4 truncate text-xl font-bold tabular-nums text-gray-950 dark:text-white" title="GHS {{ number_format($methodTotal, 2) }}">
                                GHS {{ number_format($methodTotal, 2) }}
                            </p>
                            <p class="mt-1 text-xs font-semibold tabular-nums text-gray-600 dark:text-gray-300">
                                {{ number_format($share, 1) }}% of revenue
                            </p>
                            <div
                                role="progressbar"
                                aria-label="{{ $methodLabel }} share of collected revenue"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ round($boundedShare, 1) }}"
                                class="mt-3 h-2 overflow-hidden rounded-full bg-white/80 ring-1 ring-inset ring-gray-950/5 dark:bg-white/10 dark:ring-white/10"
                            >
                                <span class="block h-full rounded-full {{ $presentation['barClasses'] }}" style="width: {{ $boundedShare }}%"></span>
                            </div>
                        </article>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No completed payments were recorded for {{ strtolower($this->periodLabel()) }}.</p>
                @endforelse
            </div>
        </x-filament::section>
            </div>

            <div
                role="status"
                aria-live="polite"
                aria-atomic="true"
                wire:loading.flex
                wire:target="applyReportPeriod,resetReportPeriod"
                class="absolute inset-0 z-10 items-start justify-center rounded-xl bg-white/75 px-4 py-12 backdrop-blur-[1px] dark:bg-gray-950/75"
                style="display: none;"
            >
                <div class="inline-flex items-center gap-3 rounded-xl border border-primary-200 bg-white px-4 py-3 font-medium text-primary-700 shadow-lg dark:border-primary-500/30 dark:bg-gray-900 dark:text-primary-300">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 animate-spin" />
                    <span>Updating revenue report&hellip;</span>
                </div>
            </div>
        </section>

        <x-filament::section heading="Report notes">
            <div class="grid gap-4 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                <p><span class="font-semibold text-gray-900 dark:text-white">Revenue received</span> includes payments marked paid or completed on their recorded payment date.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Outstanding balance</span> includes unpaid hotel, conference, table-reservation, and food-order transactions created in the selected period.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
