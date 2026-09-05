<x-filament-panels::page>
    @php($report = $this->report)

    <div class="space-y-6">
        <x-filament.report-period-controls from-label="From" until-label="Until" />

        <section aria-label="Kitchen production overview">
            @livewire(\App\Filament\Admin\Widgets\KitchenProductionReportStats::class, ['summary' => $report['summary'], 'fromDate' => $this->startDate, 'untilDate' => $this->endDate], key('kitchen-production-report-stats-'.$this->startDate.'-'.$this->endDate))
        </section>

        <x-filament::section heading="Production register" description="Filter, sort, and review finished-food performance for the selected reporting period.">
            <div data-kitchen-production-register-filters class="mb-6 rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Filter production register</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <label for="kitchen-report-search" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Search menu items
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input
                                id="kitchen-report-search"
                                type="search"
                                wire:model.live.debounce.400ms="reportSearch"
                                wire:loading.attr="disabled"
                                maxlength="100"
                                placeholder="Menu item name"
                            />
                        </x-filament::input.wrapper>
                    </label>

                    <label for="kitchen-report-category" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Category
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input.select id="kitchen-report-category" wire:model.live="categoryFilter" wire:loading.attr="disabled">
                                <option value="">All categories</option>
                                @foreach ($report['category_options'] as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>

                    <label for="kitchen-report-stock-status" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Closing stock
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input.select id="kitchen-report-stock-status" wire:model.live="stockStatus" wire:loading.attr="disabled">
                                <option value="">All closing-stock statuses</option>
                                @foreach ($this->stockStatusOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>

                    <label for="kitchen-report-sort" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Sort by
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input.select id="kitchen-report-sort" wire:model.live="sortBy" wire:loading.attr="disabled">
                                @foreach ($this->sortOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>

                    <label for="kitchen-report-sort-direction" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Direction
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input.select id="kitchen-report-sort-direction" wire:model.live="sortDirection" wire:loading.attr="disabled">
                                <option value="asc">Ascending</option>
                                <option value="desc">Descending</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>

                    <label for="kitchen-report-per-page" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Rows per page
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input.select id="kitchen-report-per-page" wire:model.live="perPage" wire:loading.attr="disabled">
                                @foreach ([10, 25, 50, 100] as $pageSize)
                                    <option value="{{ $pageSize }}">{{ $pageSize }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>
                </div>

                <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                    <div>
                        <p data-kitchen-production-register-summary aria-live="polite" class="text-sm font-semibold text-gray-950 dark:text-white">
                            Showing {{ number_format($report['rows']->total()) }} of {{ number_format($report['total_rows']) }} tracked {{ \Illuminate\Support\Str::plural('item', $report['total_rows']) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Register filters do not change the period overview metrics.</p>
                        <p wire:loading wire:target="reportSearch,categoryFilter,stockStatus,sortBy,sortDirection,perPage,resetRegisterFilters,gotoPage,previousPage,nextPage" class="mt-1 text-xs font-medium text-primary-600 dark:text-primary-400">Updating production register&hellip;</p>
                    </div>

                    <x-filament::button
                        type="button"
                        color="gray"
                        icon="heroicon-o-x-mark"
                        wire:click="resetRegisterFilters"
                        wire:loading.attr="disabled"
                        wire:target="resetRegisterFilters"
                        :disabled="! $this->hasRegisterFilters()"
                    >
                        Clear filters
                    </x-filament::button>
                </div>
            </div>

            <div data-kitchen-production-mobile-register class="space-y-4 md:hidden" aria-label="Production report cards">
                @forelse ($report['rows'] as $row)
                    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-950 dark:text-white">{{ $row['name'] }}</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['category'] }} · {{ number_format($row['usage_per_sale'], 3) }} {{ $row['unit'] }} per sale</p>
                            </div>
                            <x-filament::badge :color="match ($row['status']) { 'healthy' => 'success', 'low' => 'warning', 'negative' => 'danger' }">
                                {{ match ($row['status']) { 'healthy' => 'Healthy', 'low' => 'Low balance', 'negative' => 'Below zero' } }}
                            </x-filament::badge>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 overflow-hidden rounded-lg border border-gray-200 text-sm dark:border-gray-700">
                            <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500 dark:text-gray-400">Produced</dt><dd class="mt-1 font-medium">{{ number_format($row['produced'], 3) }} {{ $row['unit'] }}</dd></div>
                            <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500 dark:text-gray-400">Wasted</dt><dd class="mt-1 font-medium">{{ number_format($row['wasted'], 3) }} {{ $row['unit'] }}</dd></div>
                            <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500 dark:text-gray-400">Net produced</dt><dd class="mt-1 font-medium">{{ number_format($row['net_produced'], 3) }} {{ $row['unit'] }}</dd></div>
                            <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500 dark:text-gray-400">Amount sold</dt><dd class="mt-1 font-medium">{{ number_format($row['production_amount_sold'], 3) }} {{ $row['unit'] }}</dd></div>
                            <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500 dark:text-gray-400">Opening balance</dt><dd class="mt-1 font-medium">{{ number_format($row['opening_balance'], 3) }} {{ $row['unit'] }}</dd></div>
                            <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500 dark:text-gray-400">Period variance</dt><dd class="mt-1 font-medium {{ $row['period_variance'] < 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">{{ number_format($row['period_variance'], 3) }} {{ $row['unit'] }}</dd></div>
                            <div class="border-b border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500 dark:text-gray-400">Closing balance</dt><dd class="mt-1 font-medium {{ $row['closing_balance'] < 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">{{ number_format($row['closing_balance'], 3) }} {{ $row['unit'] }}</dd></div>
                            <div class="border-b border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500 dark:text-gray-400">Units sold</dt><dd class="mt-1 font-medium">{{ number_format($row['sold_units']) }}</dd></div>
                            <div class="border-r border-gray-200 p-3 dark:border-gray-700"><dt class="text-xs text-gray-500 dark:text-gray-400">Available-stock sell-through</dt><dd class="mt-1 font-medium">{{ $row['sell_through'] === null ? 'N/A' : number_format($row['sell_through'], 1).'%' }}</dd></div>
                            <div class="p-3"><dt class="text-xs text-gray-500 dark:text-gray-400">Net revenue</dt><dd class="mt-1 font-medium {{ $row['net_revenue'] < 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">GHS {{ number_format($row['net_revenue'], 2) }}</dd></div>
                        </dl>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        {{ $report['total_rows'] === 0 ? 'No production-tracked menu items found.' : 'No menu items match the current register filters.' }}
                    </p>
                @endforelse
            </div>

            <div data-kitchen-production-desktop-register class="hidden md:block">
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full min-w-[1450px] border-separate border-spacing-0 text-left text-sm">
                        <caption class="sr-only">Kitchen production, sales, and closing finished-food balance report</caption>
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="min-w-64 border-b border-r border-gray-200 px-4 py-4 dark:border-gray-700">Menu item</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Produced</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Wasted</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Net</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Units sold</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Amount sold</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Opening balance</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Period variance</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Closing balance</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Available-stock sell-through</th><th scope="col" class="border-b border-r border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Net revenue</th><th scope="col" class="border-b border-gray-200 px-4 py-4 whitespace-nowrap dark:border-gray-700">Closing stock</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900">
                            @forelse ($report['rows'] as $row)
                                <tr>
                                    <th scope="row" class="border-b border-r border-gray-200 px-4 py-4 align-top dark:border-gray-700"><p class="font-medium">{{ $row['name'] }}</p><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['category'] }} · {{ number_format($row['usage_per_sale'], 3) }} {{ $row['unit'] }} per sale</p></th>
                                    <td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['produced'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['wasted'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['net_produced'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['sold_units']) }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['production_amount_sold'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ number_format($row['opening_balance'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top font-medium whitespace-nowrap dark:border-gray-700 {{ $row['period_variance'] < 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">{{ number_format($row['period_variance'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top font-medium whitespace-nowrap dark:border-gray-700 {{ $row['closing_balance'] < 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">{{ number_format($row['closing_balance'], 3) }} {{ $row['unit'] }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700">{{ $row['sell_through'] === null ? 'N/A' : number_format($row['sell_through'], 1).'%' }}</td><td class="border-b border-r border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700 {{ $row['net_revenue'] < 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">GHS {{ number_format($row['net_revenue'], 2) }}</td>
                                    <td class="border-b border-gray-200 px-4 py-4 align-top whitespace-nowrap dark:border-gray-700"><x-filament::badge :color="match ($row['status']) { 'healthy' => 'success', 'low' => 'warning', 'negative' => 'danger' }">{{ match ($row['status']) { 'healthy' => 'Healthy', 'low' => 'Low balance', 'negative' => 'Below zero' } }}</x-filament::badge></td>
                                </tr>
                            @empty
                                <tr><td colspan="12" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">{{ $report['total_rows'] === 0 ? 'No production-tracked menu items found.' : 'No menu items match the current register filters.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($report['rows']->hasPages())
                <div data-kitchen-production-register-pagination class="mt-6 border-t border-gray-200 pt-4 dark:border-white/10">
                    {{ $report['rows']->links() }}
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
