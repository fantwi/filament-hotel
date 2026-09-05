<x-filament-panels::page>
    @php($report = $this->report)

    <div class="space-y-6">
        <x-filament.report-period-controls from-label="From" until-label="Until" />

        <section aria-label="Kitchen production overview">
            @livewire(\App\Filament\Admin\Widgets\KitchenProductionReportStats::class, ['summary' => $report['summary'], 'fromDate' => $this->startDate, 'untilDate' => $this->endDate], key('kitchen-production-report-stats-'.$this->startDate.'-'.$this->endDate))
        </section>

        <div class="space-y-4 md:hidden">
            @forelse ($report['rows'] as $row)
                <x-filament::section>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ $row['name'] }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $row['category'] }} · {{ number_format($row['usage_per_sale'], 3) }} {{ $row['unit'] }} per sale</p>
                        </div>
                        <x-filament::badge :color="match ($row['status']) { 'healthy' => 'success', 'low' => 'warning', 'negative' => 'danger' }">
                            {{ match ($row['status']) { 'healthy' => 'Healthy', 'low' => 'Low balance', 'negative' => 'Below zero' } }}
                        </x-filament::badge>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 overflow-hidden rounded-lg border border-gray-200 text-sm dark:border-gray-700">
                        <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Produced</dt><dd class="mt-1 font-medium">{{ number_format($row['produced'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Wasted</dt><dd class="mt-1 font-medium">{{ number_format($row['wasted'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Net produced</dt><dd class="mt-1 font-medium">{{ number_format($row['net_produced'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Amount sold</dt><dd class="mt-1 font-medium">{{ number_format($row['production_amount_sold'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Opening balance</dt><dd class="mt-1 font-medium">{{ number_format($row['opening_balance'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Period variance</dt><dd class="mt-1 font-medium {{ $row['period_variance'] < 0 ? 'text-danger-600' : '' }}">{{ number_format($row['period_variance'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Closing balance</dt><dd class="mt-1 font-medium {{ $row['closing_balance'] < 0 ? 'text-danger-600' : '' }}">{{ number_format($row['closing_balance'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Units sold</dt><dd class="mt-1 font-medium">{{ number_format($row['sold_units']) }}</dd></div>
                        <div class="border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Available-stock sell-through</dt><dd class="mt-1 font-medium">{{ $row['sell_through'] === null ? 'N/A' : number_format($row['sell_through'], 1).'%' }}</dd></div>
                        <div class="p-3"><dt class="text-xs text-gray-500">Net revenue</dt><dd class="mt-1 font-medium {{ $row['net_revenue'] < 0 ? 'text-danger-600' : '' }}">GHS {{ number_format($row['net_revenue'], 2) }}</dd></div>
                    </dl>
                </x-filament::section>
            @empty
                <x-filament::section><p class="py-8 text-center text-sm text-gray-500">No production-tracked menu items found.</p></x-filament::section>
            @endforelse
        </div>

        <x-filament::section class="hidden md:block">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full min-w-[1450px] border-separate border-spacing-0 text-left text-sm">
                    <caption class="sr-only">Kitchen production, sales, and closing finished-food balance report</caption>
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="min-w-64 border-b border-r border-gray-200 px-4 py-4 dark:border-gray-700">Menu item</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Produced</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Wasted</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Net</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Units sold</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Amount sold</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Opening balance</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Period variance</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Closing balance</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Available-stock sell-through</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Net revenue</th><th scope="col" class="border-b border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Closing stock</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900">
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <th scope="row" class="border-b border-r border-gray-200 px-4 py-4 align-top dark:border-gray-700"><p class="font-medium">{{ $row['name'] }}</p><p class="mt-1 text-xs text-gray-500">{{ $row['category'] }} · {{ number_format($row['usage_per_sale'], 3) }} {{ $row['unit'] }} per sale</p></th>
                                <td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['produced'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['wasted'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['net_produced'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['sold_units']) }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['production_amount_sold'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['opening_balance'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top font-medium whitespace-nowrap dark:border-gray-700 {{ $row['period_variance'] < 0 ? 'text-danger-600' : '' }}">{{ number_format($row['period_variance'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top font-medium whitespace-nowrap dark:border-gray-700 {{ $row['closing_balance'] < 0 ? 'text-danger-600' : '' }}">{{ number_format($row['closing_balance'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ $row['sell_through'] === null ? 'N/A' : number_format($row['sell_through'], 1).'%' }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700 {{ $row['net_revenue'] < 0 ? 'text-danger-600' : '' }}">GHS {{ number_format($row['net_revenue'], 2) }}</td>
                                <td class="border-b border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700"><x-filament::badge :color="match ($row['status']) { 'healthy' => 'success', 'low' => 'warning', 'negative' => 'danger' }">{{ match ($row['status']) { 'healthy' => 'Healthy', 'low' => 'Low balance', 'negative' => 'Below zero' } }}</x-filament::badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="px-4 py-10 text-center text-gray-500">No production-tracked menu items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
