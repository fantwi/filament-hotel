<x-filament-widgets::widget>
    <div class="space-y-6">
        <x-filament::section heading="Transaction summary" description="Bookings, reservations, and orders created between {{ $periodLabel }}. Payments are counted by the date they were recorded.">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Transactions</p>
                    <p class="mt-1 text-2xl font-bold">{{ number_format($totals['transactions']) }}</p>
                </div>
                <div class="rounded-xl bg-primary-50 p-4 dark:bg-primary-500/10">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Gross transaction value</p>
                    <p class="mt-1 text-2xl font-bold">GHS {{ number_format($totals['gross'], 2) }}</p>
                </div>
                <div class="rounded-xl bg-success-50 p-4 dark:bg-success-500/10">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Payments received</p>
                    <p class="mt-1 text-2xl font-bold">GHS {{ number_format($totals['payments'], 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ number_format($totals['payment_count']) }} payment(s) recorded</p>
                </div>
                <div class="rounded-xl bg-warning-50 p-4 dark:bg-warning-500/10">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Outstanding balance</p>
                    <p class="mt-1 text-2xl font-bold">GHS {{ number_format($totals['outstanding'], 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ number_format($totals['outstanding_count']) }} unpaid transaction(s)</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Transaction breakdown" description="Compare each guest transaction workflow within the selected period.">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="text-left text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Transaction type</th>
                            <th class="px-3 py-3 text-right font-semibold">Transactions</th>
                            <th class="px-3 py-3 text-right font-semibold">Gross value</th>
                            <th class="px-3 py-3 text-right font-semibold">Payments received</th>
                            <th class="px-3 py-3 text-right font-semibold">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-3 py-4 font-semibold">{{ $row['label'] }}</td>
                                <td class="px-3 py-4 text-right">{{ number_format($row['transactions']) }}</td>
                                <td class="px-3 py-4 text-right">GHS {{ number_format($row['gross'], 2) }}</td>
                                <td class="px-3 py-4 text-right">
                                    GHS {{ number_format($row['payments'], 2) }}
                                    <span class="block text-xs text-gray-500">{{ number_format($row['payment_count']) }} payment(s)</span>
                                </td>
                                <td class="px-3 py-4 text-right font-semibold">
                                    GHS {{ number_format($row['outstanding'], 2) }}
                                    <span class="block text-xs text-gray-500">{{ number_format($row['outstanding_count']) }} unpaid</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
