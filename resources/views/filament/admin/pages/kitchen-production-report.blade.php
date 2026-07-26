<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="applyFilters" class="grid gap-4 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-900 sm:grid-cols-[1fr_1fr_auto]">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                From
                <input wire:model.live="fromDate" type="date" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>

            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Until
                <input wire:model.live="untilDate" type="date" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>

            <div class="flex items-end">
                <x-filament::button type="submit" class="w-full sm:w-auto">Apply</x-filament::button>
            </div>
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            <x-filament::section><p class="text-sm text-gray-500">Tracked items</p><p class="mt-1 text-2xl font-bold">{{ number_format($this->report['summary']['tracked_items']) }}</p></x-filament::section>
            <x-filament::section><p class="text-sm text-gray-500">Healthy stock</p><p class="mt-1 text-2xl font-bold text-success-600">{{ number_format($this->report['summary']['healthy_items']) }}</p></x-filament::section>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-filament::section><p class="text-sm text-gray-500">Low stock</p><p class="mt-1 text-2xl font-bold text-warning-600">{{ number_format($this->report['summary']['low_stock_items']) }}</p></x-filament::section>
            <x-filament::section><p class="text-sm text-gray-500">Negative variance</p><p class="mt-1 text-2xl font-bold text-danger-600">{{ number_format($this->report['summary']['negative_variance_items']) }}</p></x-filament::section>
            <x-filament::section><p class="text-sm text-gray-500">Food sales revenue</p><p class="mt-1 text-2xl font-bold">GHS {{ number_format($this->report['summary']['sales_revenue'], 2) }}</p></x-filament::section>
        </div>

        <div class="space-y-4 md:hidden">
            @forelse ($this->report['rows'] as $row)
                <x-filament::section>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ $row['name'] }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $row['category'] }} · {{ number_format($row['usage_per_sale'], 3) }} {{ $row['unit'] }} per sale</p>
                        </div>
                        <x-filament::badge :color="match ($row['status']) { 'healthy' => 'success', 'low' => 'warning', 'negative' => 'danger' }">
                            {{ match ($row['status']) { 'healthy' => 'Healthy', 'low' => 'Low stock', 'negative' => 'Negative variance' } }}
                        </x-filament::badge>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 overflow-hidden rounded-lg border border-gray-200 text-sm dark:border-gray-700">
                        <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Produced</dt><dd class="mt-1 font-medium">{{ number_format($row['produced'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Wasted</dt><dd class="mt-1 font-medium">{{ number_format($row['wasted'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Net produced</dt><dd class="mt-1 font-medium">{{ number_format($row['net_produced'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Amount sold</dt><dd class="mt-1 font-medium">{{ number_format($row['production_amount_sold'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Units sold</dt><dd class="mt-1 font-medium">{{ number_format($row['sold_units']) }}</dd></div>
                        <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Sell-through</dt><dd class="mt-1 font-medium">{{ number_format($row['sell_through'], 1) }}%</dd></div>
                        <div class="border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500">Remaining</dt><dd class="mt-1 font-medium {{ $row['remaining'] < 0 ? 'text-danger-600' : '' }}">{{ number_format($row['remaining'], 3) }} {{ $row['unit'] }}</dd></div>
                        <div class="p-3"><dt class="text-xs text-gray-500">Revenue</dt><dd class="mt-1 font-medium">GHS {{ number_format($row['sales_revenue'], 2) }}</dd></div>
                    </dl>
                </x-filament::section>
            @empty
                <x-filament::section><p class="py-8 text-center text-sm text-gray-500">No production-tracked menu items found.</p></x-filament::section>
            @endforelse
        </div>

        <x-filament::section class="hidden md:block">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full min-w-[1150px] border-separate border-spacing-0 text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800">
                        <tr>
                            <th class="min-w-64 border-b border-r border-gray-200 px-4 py-4 dark:border-gray-700">Menu item</th><th class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Produced</th><th class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Wasted</th><th class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Net</th><th class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Units sold</th><th class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Amount sold</th><th class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Remaining</th><th class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Sell-through</th><th class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Revenue</th><th class="border-b border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900">
                        @forelse ($this->report['rows'] as $row)
                            <tr>
                                <td class="border-b border-r border-gray-200 px-4 py-4 align-top dark:border-gray-700"><p class="font-medium">{{ $row['name'] }}</p><p class="mt-1 text-xs text-gray-500">{{ $row['category'] }} · {{ number_format($row['usage_per_sale'], 3) }} {{ $row['unit'] }} per sale</p></td>
                                <td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['produced'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['wasted'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['net_produced'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['sold_units']) }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['production_amount_sold'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top font-medium whitespace-nowrap dark:border-gray-700 {{ $row['remaining'] < 0 ? 'text-danger-600' : '' }}">{{ number_format($row['remaining'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['sell_through'], 1) }}%</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">GHS {{ number_format($row['sales_revenue'], 2) }}</td>
                                <td class="border-b border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700"><x-filament::badge :color="match ($row['status']) { 'healthy' => 'success', 'low' => 'warning', 'negative' => 'danger' }">{{ match ($row['status']) { 'healthy' => 'Healthy', 'low' => 'Low stock', 'negative' => 'Negative variance' } }}</x-filament::badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-10 text-center text-gray-500">No production-tracked menu items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
