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

        <x-filament::section>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b text-xs uppercase text-gray-500 dark:border-gray-700">
                        <tr>
                            <th class="px-3 py-3">Menu item</th><th class="px-3 py-3">Produced</th><th class="px-3 py-3">Wasted</th><th class="px-3 py-3">Net</th><th class="px-3 py-3">Units sold</th><th class="px-3 py-3">Amount sold</th><th class="px-3 py-3">Remaining</th><th class="px-3 py-3">Sell-through</th><th class="px-3 py-3">Revenue</th><th class="px-3 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @forelse ($this->report['rows'] as $row)
                            <tr>
                                <td class="px-3 py-3"><p class="font-medium">{{ $row['name'] }}</p><p class="text-xs text-gray-500">{{ $row['category'] }} · {{ number_format($row['usage_per_sale'], 3) }} {{ $row['unit'] }} per sale</p></td>
                                <td class="px-3 py-3">{{ number_format($row['produced'], 3) }} {{ $row['unit'] }}</td><td class="px-3 py-3">{{ number_format($row['wasted'], 3) }} {{ $row['unit'] }}</td><td class="px-3 py-3">{{ number_format($row['net_produced'], 3) }} {{ $row['unit'] }}</td><td class="px-3 py-3">{{ number_format($row['sold_units']) }}</td><td class="px-3 py-3">{{ number_format($row['production_amount_sold'], 3) }} {{ $row['unit'] }}</td><td class="px-3 py-3 font-medium {{ $row['remaining'] < 0 ? 'text-danger-600' : '' }}">{{ number_format($row['remaining'], 3) }} {{ $row['unit'] }}</td><td class="px-3 py-3">{{ number_format($row['sell_through'], 1) }}%</td><td class="px-3 py-3">GHS {{ number_format($row['sales_revenue'], 2) }}</td>
                                <td class="px-3 py-3"><x-filament::badge :color="match ($row['status']) { 'healthy' => 'success', 'low' => 'warning', 'negative' => 'danger' }">{{ match ($row['status']) { 'healthy' => 'Healthy', 'low' => 'Low stock', 'negative' => 'Negative variance' } }}</x-filament::badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-3 py-10 text-center text-gray-500">No production-tracked menu items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
