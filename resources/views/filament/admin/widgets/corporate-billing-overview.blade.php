<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Corporate billing</x-slot>
        <x-slot name="description">Deferred-payment revenue and current credit exposure.</x-slot>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-primary-50 p-4 dark:bg-primary-500/10"><p class="text-sm text-gray-600 dark:text-gray-300">Billed this month</p><p class="mt-1 text-2xl font-bold">GHS {{ number_format($overview['billed_this_month'], 2) }}</p></div>
            <div class="rounded-xl bg-warning-50 p-4 dark:bg-warning-500/10"><p class="text-sm text-gray-600 dark:text-gray-300">Corporate receivables</p><p class="mt-1 text-2xl font-bold">GHS {{ number_format($overview['outstanding'], 2) }}</p></div>
            <div class="rounded-xl bg-success-50 p-4 dark:bg-success-500/10"><p class="text-sm text-gray-600 dark:text-gray-300">Available credit</p><p class="mt-1 text-2xl font-bold">GHS {{ number_format($overview['available_credit'], 2) }}</p><p class="mt-1 text-xs text-gray-500">Across limited accounts</p></div>
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5"><p class="text-sm text-gray-600 dark:text-gray-300">Corporate accounts</p><p class="mt-1 text-2xl font-bold">{{ number_format($overview['active_accounts']) }}</p><p class="mt-1 text-xs text-gray-500">{{ number_format($overview['linked_guests']) }} linked guest(s)</p></div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead class="text-left text-gray-600 dark:text-gray-300"><tr><th class="px-3 py-3 font-semibold">Organisation</th><th class="px-3 py-3 font-semibold">Linked guests</th><th class="px-3 py-3 text-right font-semibold">Credit limit</th><th class="px-3 py-3 text-right font-semibold">Outstanding</th><th class="px-3 py-3 text-right font-semibold">Available</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($overview['accounts'] as $account)
                        <tr><td class="px-3 py-3 font-medium">{{ $account['name'] }}</td><td class="px-3 py-3">{{ number_format($account['linked_guests']) }}</td><td class="px-3 py-3 text-right">{{ $account['credit_limit'] === null ? 'No limit' : 'GHS '.number_format($account['credit_limit'], 2) }}</td><td class="px-3 py-3 text-right font-semibold">GHS {{ number_format($account['outstanding'], 2) }}</td><td class="px-3 py-3 text-right">{{ $account['available_credit'] === null ? 'Unlimited' : 'GHS '.number_format($account['available_credit'], 2) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">No enabled corporate accounts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
