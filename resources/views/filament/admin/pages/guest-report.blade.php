<x-filament::page>
    @php
        $report = $this->report();
        $drillDownUrls = $this->drillDownUrls();
        $activityPresentations = [
            'hotel' => [
                'label' => 'Hotel bookings',
                'icon' => 'heroicon-o-building-office-2',
                'classes' => 'border-primary-200 bg-primary-50/70 dark:border-primary-500/20 dark:bg-primary-500/10',
                'iconClasses' => 'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300',
                'barClasses' => 'bg-primary-500',
            ],
            'conference' => [
                'label' => 'Conference bookings',
                'icon' => 'heroicon-o-building-office',
                'classes' => 'border-info-200 bg-info-50/70 dark:border-info-500/20 dark:bg-info-500/10',
                'iconClasses' => 'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-300',
                'barClasses' => 'bg-info-500',
            ],
            'table' => [
                'label' => 'Table reservations',
                'icon' => 'heroicon-o-calendar-days',
                'classes' => 'border-warning-200 bg-warning-50/70 dark:border-warning-500/20 dark:bg-warning-500/10',
                'iconClasses' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
                'barClasses' => 'bg-warning-500',
            ],
            'food' => [
                'label' => 'Food orders',
                'icon' => 'heroicon-o-shopping-bag',
                'classes' => 'border-success-200 bg-success-50/70 dark:border-success-500/20 dark:bg-success-500/10',
                'iconClasses' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
                'barClasses' => 'bg-success-500',
            ],
            'other' => [
                'label' => 'Direct or uncategorized payments',
                'icon' => 'heroicon-o-banknotes',
                'classes' => 'border-gray-200 bg-gray-50/70 dark:border-white/10 dark:bg-white/5',
                'iconClasses' => 'bg-gray-200 text-gray-700 dark:bg-white/10 dark:text-gray-300',
                'barClasses' => 'bg-gray-500',
            ],
        ];
    @endphp
    <div class="space-y-6">
        <x-filament::section>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Guest insights</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">Guest activity report</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Track new guest profiles, collected payments, repeat service use, and guest revenue for one reporting period.</p>
            </div>
            <x-filament.report-period-controls id="guest-report-period-controls" class="mt-5" />
        </x-filament::section>

        <section
            aria-label="Selected period guest results"
            class="relative"
            wire:loading.attr="aria-busy"
            wire:target="applyReportPeriod,resetReportPeriod"
        >
            <div
                data-guest-period-results
                class="space-y-6 transition-opacity duration-200"
                wire:loading.class="pointer-events-none opacity-60"
                wire:target="applyReportPeriod,resetReportPeriod"
            >
        <section aria-labelledby="guest-overview-heading" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,20rem)] lg:items-stretch">
                <div class="flex min-w-0 flex-col justify-center">
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Selected period</p>
                    <h2 id="guest-overview-heading" class="mt-1 text-xl font-bold tracking-tight text-gray-950 dark:text-white">Guest overview</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">New profiles, collected payments, repeat service use, and average revenue for the selected reporting period.</p>
                </div>

                <div
                    role="note"
                    aria-label="All-time guest base"
                    class="flex min-w-0 items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5"
                >
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                        <x-filament::icon icon="heroicon-o-users" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">All-time guest base</p>
                        <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-300">Guest profiles</p>
                        <p class="text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ number_format($report['totalGuests']) }}</p>
                        @if ($drillDownUrls['guestProfiles'])
                            <a href="{{ $drillDownUrls['guestProfiles'] }}" aria-label="View all guest profiles" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary-700 hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
                                View guest register
                                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4" />
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @if ($report['hasPeriodActivity'])
                @livewire(\App\Filament\Admin\Widgets\GuestStats::class, [
                    'reportData' => [
                        'newGuests' => $report['newGuests'],
                        'payingGuests' => $report['payingGuests'],
                        'returningGuests' => $report['returningGuests'],
                        'averageSpend' => $report['averageSpend'],
                    ],
                    'reportPeriodLabel' => $this->periodLabel(),
                    'drillDownUrls' => [
                        'newGuests' => $drillDownUrls['newGuests'],
                        'payingGuests' => $drillDownUrls['payingGuests'],
                        'returningGuests' => $drillDownUrls['returningGuests'],
                        'averageSpend' => $drillDownUrls['averageSpend'],
                    ],
                ], key('guest-stats-'.$this->period.'-'.$this->startDate.'-'.$this->endDate))
            @endif
        </section>

        @if ($report['hasPeriodActivity'])
        <section aria-label="Previous-period guest comparison">
            @livewire(\App\Filament\Admin\Widgets\GuestComparisonStats::class, [
                'comparison' => $report['comparison'],
            ], key('guest-comparison-stats-'.$this->period.'-'.$this->startDate.'-'.$this->endDate))
        </section>

        <section aria-label="Guest trend chart">
            @livewire(\App\Filament\Admin\Widgets\GuestTrendChart::class, [
                'trend' => $report['trend'],
                'periodLabel' => $this->periodLabel(),
            ], key('guest-trend-chart-'.$this->period.'-'.$this->startDate.'-'.$this->endDate))
        </section>

        <x-filament::section heading="Guest revenue" description="Collections and refunds recorded in the selected reporting period">
            <dl class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-500/20 dark:bg-primary-500/10">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Gross guest revenue</dt>
                    <dd class="mt-1 text-2xl font-bold tabular-nums text-primary-700 dark:text-primary-300">GHS {{ number_format($report['totalPaid'], 2) }}</dd>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Guest payments collected in the selected period</p>
                    @if ($drillDownUrls['grossSpend'])
                        <a href="{{ $drillDownUrls['grossSpend'] }}" aria-label="View gross guest revenue payments" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-primary-700 hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
                            View payments
                            <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4" />
                        </a>
                    @endif
                </div>
                <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-500/20 dark:bg-danger-500/10">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Refunds processed</dt>
                    <dd class="mt-1 text-2xl font-bold tabular-nums text-danger-700 dark:text-danger-300">GHS {{ number_format($report['refundTotal'], 2) }}</dd>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($report['refundCount']) }} {{ $report['refundCount'] === 1 ? 'refund' : 'refunds' }} processed in the selected period</p>
                    @if ($drillDownUrls['refunds'])
                        <a href="{{ $drillDownUrls['refunds'] }}" aria-label="View guest refund payments" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-danger-700 hover:text-danger-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger-500 dark:text-danger-300 dark:hover:text-danger-200">
                            View refunds
                            <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4" />
                        </a>
                    @endif
                </div>
                <div @class([
                    'rounded-xl border p-4',
                    'border-success-200 bg-success-50 dark:border-success-500/20 dark:bg-success-500/10' => $report['netSpend'] > 0,
                    'border-danger-200 bg-danger-50 dark:border-danger-500/20 dark:bg-danger-500/10' => $report['netSpend'] < 0,
                    'border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5' => $report['netSpend'] === 0.0,
                ])>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Net guest revenue</dt>
                    <dd @class([
                        'mt-1 text-2xl font-bold tabular-nums',
                        'text-success-700 dark:text-success-300' => $report['netSpend'] > 0,
                        'text-danger-700 dark:text-danger-300' => $report['netSpend'] < 0,
                        'text-gray-900 dark:text-white' => $report['netSpend'] === 0.0,
                    ])>GHS {{ number_format($report['netSpend'], 2) }}</dd>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Gross guest revenue less processed refunds</p>
                    @if ($drillDownUrls['netSpend'])
                        <a href="{{ $drillDownUrls['netSpend'] }}" aria-label="View net guest revenue analysis" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-gray-700 hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-300 dark:hover:text-primary-300">
                            View analysis
                            <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4" />
                        </a>
                    @endif
                </div>
            </dl>
        </x-filament::section>

        <section class="grid gap-4 lg:grid-cols-3">
            <x-filament::section aria-label="Paid service mix" heading="Paid service mix" description="Collected guest payments by service for the selected reporting period">
                @if ($report['paymentCount'] > 0)
                    <div class="space-y-3">
                        <p class="text-sm font-semibold tabular-nums text-gray-700 dark:text-gray-200">
                            {{ number_format($report['paymentCount']) }} collected {{ $report['paymentCount'] === 1 ? 'payment' : 'payments' }}
                        </p>

                        @foreach ($activityPresentations as $key => $presentation)
                            @php
                                $count = $report['activity'][$key];
                                $share = ($count / $report['paymentCount']) * 100;
                                $boundedShare = max(0, min(100, $share));
                            @endphp
                            <article data-service-mix-channel="{{ $key }}" class="min-w-0 rounded-xl border p-3 {{ $presentation['classes'] }}">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $presentation['iconClasses'] }}">
                                        <x-filament::icon :icon="$presentation['icon']" class="h-5 w-5" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $presentation['label'] }}</h3>
                                            @if ($drillDownUrls['activity'][$key])
                                                <a href="{{ $drillDownUrls['activity'][$key] }}" aria-label="View {{ $key }} guest payment activity" class="shrink-0 rounded-md text-gray-500 transition hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-400 dark:hover:text-primary-300">
                                                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4" />
                                                </a>
                                            @endif
                                        </div>
                                        <div class="mt-1 flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 text-xs">
                                            <p class="font-semibold tabular-nums text-gray-700 dark:text-gray-200">
                                                {{ number_format($count) }} {{ $count === 1 ? 'payment' : 'payments' }}
                                            </p>
                                            <p class="font-semibold tabular-nums text-gray-600 dark:text-gray-300">{{ number_format($share, 1) }}%</p>
                                        </div>
                                        <div
                                            role="progressbar"
                                            aria-label="{{ $presentation['label'] }} share of collected guest payments"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            aria-valuenow="{{ round($boundedShare, 1) }}"
                                            class="mt-2 h-2 overflow-hidden rounded-full bg-white/80 ring-1 ring-inset ring-gray-950/5 dark:bg-white/10 dark:ring-white/10"
                                        >
                                            <span class="block h-full rounded-full {{ $presentation['barClasses'] }}" style="width: {{ $boundedShare }}%"></span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div role="status" aria-label="No collected payment activity" class="flex flex-col items-center px-3 py-8 text-center">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300">
                            <x-filament::icon icon="heroicon-o-banknotes" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-4 text-sm font-semibold text-gray-950 dark:text-white">No collected payment activity</h3>
                        <p class="mt-2 text-xs leading-5 text-gray-600 dark:text-gray-300">No guest payments were collected during {{ strtolower($this->periodLabel()) }}.</p>
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section heading="Top guests by gross revenue" description="Highest gross guest revenue in the selected reporting period" class="lg:col-span-2">
                @if ($report['topGuests']->isEmpty())
                    <div role="status" aria-label="No top guests" class="flex flex-col items-center px-4 py-10 text-center sm:py-12">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300">
                            <x-filament::icon icon="heroicon-o-user-group" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">No guests with collected payments</h3>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600 dark:text-gray-300">No guest payments were collected in the selected reporting period.</p>
                    </div>
                @else
                    <ol aria-label="Top guests mobile list" class="space-y-3 md:hidden">
                        @foreach ($report['topGuests'] as $payment)
                            @php($guestDetailsUrl = $this->guestDetailsUrl($payment->guest))
                            <li class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300" aria-label="Rank {{ $loop->iteration }}">
                                        {{ $loop->iteration }}
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="break-words font-semibold text-gray-950 dark:text-white">
                                            @if ($guestDetailsUrl)
                                                <a href="{{ $guestDetailsUrl }}" aria-label="View guest {{ $payment->guest?->full_name }}" class="hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:text-primary-300">
                                                    {{ $payment->guest?->full_name }}
                                                </a>
                                            @else
                                                {{ $payment->guest?->full_name ?? 'Guest not recorded' }}
                                            @endif
                                        </h3>
                                        <p class="mt-1 break-all text-xs text-gray-500 dark:text-gray-400">{{ $payment->guest?->email ?? 'Email not recorded' }}</p>
                                    </div>
                                </div>

                                <dl class="mt-4 grid grid-cols-2 gap-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-white/10">
                                    <div class="min-w-0">
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Payments</dt>
                                        <dd class="mt-1 font-semibold tabular-nums text-gray-950 dark:text-white">{{ number_format($payment->payment_count) }}</dd>
                                    </div>
                                    <div class="min-w-0 text-right">
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Gross revenue</dt>
                                        <dd class="mt-1 break-words font-bold tabular-nums text-primary-700 dark:text-primary-300">GHS {{ number_format($payment->total_spend, 2) }}</dd>
                                    </div>
                                </dl>
                            </li>
                        @endforeach
                    </ol>

                    <div aria-label="Top guests desktop table" class="hidden md:block">
                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
                            <table class="w-full min-w-[640px] divide-y divide-gray-200 text-sm dark:divide-white/10">
                                <caption class="sr-only">Top guests by gross revenue</caption>
                                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 font-semibold">Guest</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">Email</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold">Payments</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold">Gross revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                                    @foreach ($report['topGuests'] as $payment)
                                        @php($guestDetailsUrl = $this->guestDetailsUrl($payment->guest))
                                        <tr class="align-top hover:bg-gray-50 dark:hover:bg-white/5">
                                            <th scope="row" class="max-w-56 break-words px-4 py-4 font-semibold text-gray-950 dark:text-white">
                                                @if ($guestDetailsUrl)
                                                    <a href="{{ $guestDetailsUrl }}" aria-label="View guest {{ $payment->guest?->full_name }}" class="hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:text-primary-300">
                                                        {{ $payment->guest?->full_name }}
                                                    </a>
                                                @else
                                                    {{ $payment->guest?->full_name ?? 'Guest not recorded' }}
                                                @endif
                                            </th>
                                            <td class="max-w-64 break-all px-4 py-4 text-gray-500 dark:text-gray-400">{{ $payment->guest?->email ?? 'Email not recorded' }}</td>
                                            <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums">{{ number_format($payment->payment_count) }}</td>
                                            <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums">GHS {{ number_format($payment->total_spend, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </x-filament::section>
        </section>
        @else
            <x-filament::section>
                <div role="status" aria-label="No guest activity for selected period" class="flex flex-col items-center px-4 py-12 text-center sm:py-16">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="h-7 w-7" />
                    </span>
                    <h2 class="mt-5 text-lg font-bold tracking-tight text-gray-950 dark:text-white">No guest activity in this period</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600 dark:text-gray-300">No new guest profiles, collected payments, or processed refunds were recorded in the selected reporting period.</p>
                    <a
                        href="#guest-report-period-controls"
                        class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                    >
                        Change reporting period
                        <x-filament::icon icon="heroicon-m-arrow-up" class="h-4 w-4" />
                    </a>
                </div>
            </x-filament::section>
        @endif
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
                    <span>Updating guest report&hellip;</span>
                </div>
            </div>
        </section>

        <x-filament::section heading="How these metrics are calculated">
            <div class="grid gap-4 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                <p><span class="font-semibold text-gray-900 dark:text-white">Guests with collected payments</span> are distinct guests linked to payments received in the selected period, including payments refunded later.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Guest revenue</span> records collections by payment date and refunds by processing date; transactions without an associated guest are excluded.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
