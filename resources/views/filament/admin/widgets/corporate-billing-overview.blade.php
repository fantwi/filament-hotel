<x-filament-widgets::widget>
    <div class="space-y-5">
        <x-filament::section>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Corporate finance</p>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight">Corporate billing and credit exposure</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">Compare billing raised during the selected period with current receivables and remaining credit capacity.</p>
                </div>
                <div class="shrink-0 rounded-xl bg-gray-100 px-4 py-3 dark:bg-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Applied reporting period</p>
                    <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ $periodLabel }}</p>
                </div>
            </div>
        </x-filament::section>

        <section class="grid gap-5 lg:grid-cols-4">
            <div class="space-y-3 lg:col-span-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Selected-period activity</p>
                <article class="rounded-2xl border border-primary-100 bg-primary-50 p-5 shadow-sm dark:border-primary-500/20 dark:bg-primary-500/10">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Corporate billing raised</p>
                    <p class="mt-2 text-3xl font-bold text-primary-700 dark:text-primary-300">GHS {{ number_format($overview['billed_in_period'], 2) }}</p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Deferred-payment transactions created in the applied period</p>
                </article>
            </div>

            <div class="space-y-3 lg:col-span-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Current exposure — all periods</p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article class="rounded-2xl border border-warning-100 bg-warning-50 p-5 shadow-sm dark:border-warning-500/20 dark:bg-warning-500/10">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total outstanding</p>
                        <p class="mt-2 text-3xl font-bold text-warning-700 dark:text-warning-300">GHS {{ number_format($overview['outstanding'], 2) }}</p>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Current unpaid corporate balance across all periods</p>
                    </article>

                    <article class="rounded-2xl border border-success-100 bg-success-50 p-5 shadow-sm dark:border-success-500/20 dark:bg-success-500/10">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Available credit</p>
                        <p class="mt-2 text-3xl font-bold text-success-700 dark:text-success-300">GHS {{ number_format($overview['available_credit'], 2) }}</p>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Across accounts with a configured limit</p>
                    </article>

                    <article class="rounded-2xl border border-gray-200 bg-gray-50 p-5 shadow-sm dark:border-white/10 dark:bg-white/5 sm:col-span-2 lg:col-span-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Enabled accounts</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($overview['active_accounts']) }}</p>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ number_format($overview['linked_guests']) }} linked guest account(s)</p>
                    </article>
                </div>
            </div>
        </section>

        <x-filament::section>
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Highest corporate exposures</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Showing the five accounts with the highest outstanding balances.</p>
                </div>
                <x-filament::button
                    tag="a"
                    :href="$accountsUrl"
                    color="gray"
                    icon="heroicon-o-arrow-top-right-on-square"
                    class="w-full sm:w-auto"
                >
                    View all corporate accounts
                </x-filament::button>
            </div>

            <div class="grid gap-3 md:hidden">
                @if ($overview['accounts']->isNotEmpty())
                    @foreach ($overview['accounts'] as $account)
                        @php
                            $utilisation = $account['credit_limit'] === null
                                ? null
                                : min(100, ($account['outstanding'] / max(1, $account['credit_limit'])) * 100);
                        @endphp
                        <article class="rounded-2xl border border-gray-200 p-4 dark:border-white/10">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $account['name'] }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ number_format($account['linked_guests']) }} linked guest(s)</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $utilisation !== null && $utilisation >= 90 ? 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300' : 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' }}">
                                    {{ $utilisation === null ? 'No limit' : number_format($utilisation, 0).'% used' }}
                                </span>
                            </div>
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><dt class="text-gray-500 dark:text-gray-400">Outstanding</dt><dd class="mt-1 font-bold text-warning-700 dark:text-warning-300">GHS {{ number_format($account['outstanding'], 2) }}</dd></div>
                                <div><dt class="text-gray-500 dark:text-gray-400">Available credit</dt><dd class="mt-1 font-bold text-success-700 dark:text-success-300">{{ $account['available_credit'] === null ? 'Unlimited' : 'GHS '.number_format($account['available_credit'], 2) }}</dd></div>
                            </dl>
                            @if ($utilisation !== null)
                                <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10"><div class="h-full rounded-full {{ $utilisation >= 90 ? 'bg-danger-500' : ($utilisation >= 70 ? 'bg-warning-500' : 'bg-success-500') }}" style="width: {{ $utilisation }}%"></div></div>
                            @endif
                        </article>
                    @endforeach
                @else
                    <p class="rounded-xl bg-gray-50 p-5 text-center text-sm text-gray-500 dark:bg-white/5 dark:text-gray-400">No enabled corporate accounts.</p>
                @endif
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <caption class="sr-only">Corporate account credit positions</caption>
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Organisation</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Linked guests</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Credit limit</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Outstanding</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Available</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Credit utilisation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @if ($overview['accounts']->isNotEmpty())
                            @foreach ($overview['accounts'] as $account)
                                @php
                                    $utilisation = $account['credit_limit'] === null
                                        ? null
                                        : min(100, ($account['outstanding'] / max(1, $account['credit_limit'])) * 100);
                                @endphp
                                <tr>
                                    <th scope="row" class="px-4 py-4 font-semibold text-gray-900 dark:text-white">{{ $account['name'] }}</th>
                                    <td class="px-4 py-4">{{ number_format($account['linked_guests']) }}</td>
                                    <td class="px-4 py-4 text-right">{{ $account['credit_limit'] === null ? 'No limit' : 'GHS '.number_format($account['credit_limit'], 2) }}</td>
                                    <td class="px-4 py-4 text-right font-semibold text-warning-700 dark:text-warning-300">GHS {{ number_format($account['outstanding'], 2) }}</td>
                                    <td class="px-4 py-4 text-right font-semibold text-success-700 dark:text-success-300">{{ $account['available_credit'] === null ? 'Unlimited' : 'GHS '.number_format($account['available_credit'], 2) }}</td>
                                    <td class="min-w-44 px-4 py-4">
                                        @if ($utilisation === null)
                                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">No limit</span>
                                        @else
                                            <div class="flex items-center gap-3">
                                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10"><div class="h-full rounded-full {{ $utilisation >= 90 ? 'bg-danger-500' : ($utilisation >= 70 ? 'bg-warning-500' : 'bg-success-500') }}" style="width: {{ $utilisation }}%"></div></div>
                                                <span class="w-12 text-right text-xs font-semibold">{{ number_format($utilisation, 0) }}%</span>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">No enabled corporate accounts.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Billing guidance">
            <div class="grid gap-4 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                <p><span class="font-semibold text-gray-900 dark:text-white">Corporate billing raised</span> includes valid deferred-payment transactions created during the selected reporting period.</p>
                <p><span class="font-semibold text-gray-900 dark:text-white">Outstanding and credit utilisation</span> use current unpaid balances across all periods. Accounts without a limit are labelled as unlimited.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
