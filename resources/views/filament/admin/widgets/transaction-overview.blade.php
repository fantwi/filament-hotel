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

        <section class="grid gap-4 lg:grid-cols-3">
            <x-filament::section heading="Outstanding follow-up" description="Unpaid transactions created in {{ $periodLabel }}">
                <div class="space-y-4">
                    <div class="rounded-xl bg-warning-50 p-4 dark:bg-warning-500/10">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Corporate credit awaiting payment</p>
                        <p class="mt-1 text-2xl font-bold text-warning-700 dark:text-warning-300">GHS {{ number_format($totals['corporate_outstanding'], 2) }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totals['corporate_outstanding_count']) }} corporate transaction(s)</p>
                    </div>
                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">Corporate balances remain open until a payment is recorded or an accountant clears the transaction as paid.</p>
                </div>
            </x-filament::section>

            <x-filament::section heading="Transaction mix" description="Created during {{ $periodLabel }}" class="lg:col-span-2">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($rows as $row)
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                            <p class="text-sm font-semibold">{{ $row['label'] }}</p>
                            <p class="mt-2 text-2xl font-bold">{{ number_format($row['transactions']) }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">GHS {{ number_format($row['gross'], 2) }} gross value</p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </section>

        <x-filament::section heading="Transaction breakdown" description="Compare activity, payments, and outstanding balances for each guest transaction workflow.">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($rows as $row)
                    <article class="flex h-full flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-semibold text-gray-950 dark:text-white">{{ $row['label'] }}</h3>
                            <span class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">{{ number_format($row['transactions']) }} total</span>
                        </div>
                        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Gross value</dt>
                                <dd class="mt-1 font-semibold text-gray-950 dark:text-white">GHS {{ number_format($row['gross'], 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Payments received</dt>
                                <dd class="mt-1 font-semibold text-gray-950 dark:text-white">GHS {{ number_format($row['payments'], 2) }}</dd>
                                <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($row['payment_count']) }} payment(s)</dd>
                            </div>
                            <div class="col-span-2 rounded-lg bg-warning-50 p-3 dark:bg-warning-500/10">
                                <dt class="text-xs text-warning-800 dark:text-warning-200">Outstanding</dt>
                                <dd class="mt-1 font-semibold text-warning-700 dark:text-warning-300">GHS {{ number_format($row['outstanding'], 2) }}</dd>
                                <dd class="mt-1 text-xs text-warning-700/80 dark:text-warning-200/80">{{ number_format($row['outstanding_count']) }} unpaid transaction(s)</dd>
                            </div>
                        </dl>
                        <div class="mt-4 border-t border-gray-100 pt-3 text-xs dark:border-white/10">
                            <dt class="text-gray-500 dark:text-gray-400">Corporate outstanding</dt>
                            <dd class="mt-1 font-semibold text-gray-800 dark:text-gray-200">GHS {{ number_format($row['corporate_outstanding'], 2) }}</dd>
                            <dd class="mt-1 text-gray-500 dark:text-gray-400">{{ number_format($row['corporate_outstanding_count']) }} corporate transaction(s)</dd>
                        </div>
                    </article>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section heading="Report notes">
            <div class="grid gap-4 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                <p><span class="font-semibold text-gray-900 dark:text-white">Transactions</span> are filtered by their creation date. Cancelled, expired, and no-show records are excluded from gross and outstanding values.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Payments received</span> are filtered by the date each completed payment was recorded, so they may settle transactions created in an earlier period.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
