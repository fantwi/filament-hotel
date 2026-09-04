<x-filament-widgets::widget>
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Transaction insights</p>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight">Bookings, reservations, and orders</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">See the value created, payments recorded, and balances that still need follow-up for the selected dashboard period.</p>
                </div>
                <p class="shrink-0 rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">{{ $periodLabel }}</p>
            </div>
        </x-filament::section>

        @if (! $hasActivity)
            <x-filament::section>
                <div class="flex flex-col items-center px-4 py-10 text-center sm:py-14">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="h-6 w-6" />
                    </span>
                    <h3 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">No transaction activity</h3>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600 dark:text-gray-300">No bookings, reservations, payments, or refunds were recorded from {{ $periodLabel }}.</p>
                    <p class="mt-1 max-w-xl text-sm text-gray-500 dark:text-gray-400">Choose another period above and apply the filters to review a different range.</p>
                </div>
            </x-filament::section>
        @else
        <x-filament::section heading="How metrics are scoped" description="The selected period is applied to the event date named on each metric.">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-500/20 dark:bg-primary-500/10">
                    <p class="text-sm font-semibold text-primary-800 dark:text-primary-200">Transaction activity</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Counts use the transaction creation date and include every status. Gross values exclude cancelled, expired, and no-show records where those statuses apply.</p>
                </div>
                <div class="rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-500/20 dark:bg-success-500/10">
                    <p class="text-sm font-semibold text-success-800 dark:text-success-200">Payment activity</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Completed payments use their recorded date. Refunds use their processed date, so either can relate to transactions created before the selected period.</p>
                </div>
                <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-500/20 dark:bg-warning-500/10">
                    <p class="text-sm font-semibold text-warning-800 dark:text-warning-200">Outstanding balances</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Receivables use the transaction creation date and subtract all completed payments. Collection rate compares those payments with active gross value for the same transaction cohort.</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section id="collection-performance" heading="Collection performance" description="Payments and refunds processed in {{ $periodLabel }}, plus collection progress for transactions created in that period.">
            <div class="grid gap-4 md:grid-cols-3">
                <a href="{{ $links['net_collections'] }}" aria-label="Open net collection details" @class([
                    'group block rounded-xl border p-4 transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900',
                    'border-success-200 bg-success-50 dark:border-success-500/20 dark:bg-success-500/10' => $totals['net_collections'] >= 0,
                    'border-danger-200 bg-danger-50 dark:border-danger-500/20 dark:bg-danger-500/10' => $totals['net_collections'] < 0,
                ])>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Net collections</p>
                    <p @class([
                        'mt-1 text-2xl font-bold',
                        'text-success-700 dark:text-success-300' => $totals['net_collections'] >= 0,
                        'text-danger-700 dark:text-danger-300' => $totals['net_collections'] < 0,
                    ])>GHS {{ number_format($totals['net_collections'], 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Completed payments less refunds processed</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-gray-700 dark:text-gray-200">View details <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></span>
                </a>
                <a href="{{ $links['refunds'] }}" aria-label="Open refund details" class="group block rounded-xl border border-danger-200 bg-danger-50 p-4 transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:border-danger-500/20 dark:bg-danger-500/10 dark:focus-visible:ring-offset-gray-900">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Refunds processed</p>
                    <p class="mt-1 text-2xl font-bold text-danger-700 dark:text-danger-300">GHS {{ number_format($totals['refunds'], 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totals['refund_count']) }} refund(s)</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-danger-700 dark:text-danger-300">View details <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></span>
                </a>
                <a href="{{ $links['collection_rate'] }}" aria-label="Open collection rate details" class="group block rounded-xl border border-info-200 bg-info-50 p-4 transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:border-info-500/20 dark:bg-info-500/10 dark:focus-visible:ring-offset-gray-900">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Collection rate</p>
                    <p class="mt-1 text-2xl font-bold text-info-700 dark:text-info-300">{{ number_format($totals['collection_rate'], 1) }}%</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">GHS {{ number_format($totals['cohort_collections'], 2) }} collected against active period transactions</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-info-700 dark:text-info-300">View details <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></span>
                </a>
            </div>
        </x-filament::section>

        <x-filament::section id="outstanding-follow-up" heading="Outstanding follow-up" description="Unpaid transactions created in {{ $periodLabel }}">
            <div class="grid gap-4 md:grid-cols-3">
                <a href="{{ $links['non_corporate'] }}" aria-label="Open non-corporate outstanding details" class="group block rounded-xl border border-info-200 bg-info-50 p-4 transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:border-info-500/20 dark:bg-info-500/10 dark:focus-visible:ring-offset-gray-900">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Non-corporate outstanding</p>
                    <p class="mt-1 text-2xl font-bold text-info-700 dark:text-info-300">GHS {{ number_format($totals['non_corporate_outstanding'], 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totals['non_corporate_outstanding_count']) }} guest transaction(s)</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-info-700 dark:text-info-300">View details <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></span>
                </a>
                <a href="{{ $links['corporate'] }}" aria-label="Open corporate outstanding details" class="group block rounded-xl border border-warning-200 bg-warning-50 p-4 transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:border-warning-500/20 dark:bg-warning-500/10 dark:focus-visible:ring-offset-gray-900">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Corporate outstanding</p>
                    <p class="mt-1 text-2xl font-bold text-warning-700 dark:text-warning-300">GHS {{ number_format($totals['corporate_outstanding'], 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totals['corporate_outstanding_count']) }} corporate transaction(s)</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-warning-700 dark:text-warning-300">View details <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></span>
                </a>
                <a href="{{ $links['overdue_corporate'] }}" aria-label="Open overdue corporate details" class="group block rounded-xl border border-danger-200 bg-danger-50 p-4 transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:border-danger-500/20 dark:bg-danger-500/10 dark:focus-visible:ring-offset-gray-900">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Overdue corporate</p>
                    <p class="mt-1 text-2xl font-bold text-danger-700 dark:text-danger-300">GHS {{ number_format($totals['overdue_corporate_outstanding'], 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totals['overdue_corporate_outstanding_count']) }} past payment terms</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-danger-700 dark:text-danger-300">View details <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></span>
                </a>
            </div>
        </x-filament::section>

        <x-filament::section id="transaction-breakdown" heading="Transaction breakdown" description="Compare activity, payments, and outstanding balances for each guest transaction workflow.">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($rows as $row)
                    <a href="{{ $row['url'] }}" aria-label="Open {{ $row['label'] }}" class="group block rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                        <article class="flex h-full flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition group-hover:-translate-y-0.5 group-hover:shadow-md dark:border-white/10 dark:bg-white/5">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-semibold text-gray-950 dark:text-white">{{ $row['label'] }}</h3>
                                <span class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">{{ number_format($row['transactions']) }} created</span>
                            </div>
                            <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Active gross value</dt>
                                    <dd class="mt-1 font-semibold text-gray-950 dark:text-white">GHS {{ number_format($row['gross'], 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Completed payments recorded</dt>
                                    <dd class="mt-1 font-semibold text-gray-950 dark:text-white">GHS {{ number_format($row['payments'], 2) }}</dd>
                                    <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($row['payment_count']) }} payment(s)</dd>
                                </div>
                                <div class="col-span-2 rounded-lg bg-warning-50 p-3 dark:bg-warning-500/10">
                                    <dt class="text-xs text-warning-800 dark:text-warning-200">Outstanding from period</dt>
                                    <dd class="mt-1 font-semibold text-warning-700 dark:text-warning-300">GHS {{ number_format($row['outstanding'], 2) }}</dd>
                                    <dd class="mt-1 text-xs text-warning-700/80 dark:text-warning-200/80">{{ number_format($row['outstanding_count']) }} unpaid transaction(s)</dd>
                                </div>
                            </dl>
                            <dl class="mt-4 border-t border-gray-100 pt-3 text-xs dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Corporate outstanding from period</dt>
                                <dd class="mt-1 font-semibold text-gray-800 dark:text-gray-200">GHS {{ number_format($row['corporate_outstanding'], 2) }}</dd>
                                <dd class="mt-1 text-gray-500 dark:text-gray-400">{{ number_format($row['corporate_outstanding_count']) }} corporate transaction(s)</dd>
                            </dl>
                            <span class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-primary-700 dark:text-primary-300">View records <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></span>
                        </article>
                    </a>
                @endforeach
            </div>
        </x-filament::section>

        @endif

    </div>
</x-filament-widgets::widget>
