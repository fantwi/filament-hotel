<x-filament-panels::page>
    @php($report = $this->report)

    <div data-kitchen-production-report-page class="space-y-6">
        <x-filament.report-period-controls id="kitchen-production-report-period-controls" from-label="From" until-label="Until" />

        <section
            aria-label="Kitchen production report results"
            class="relative"
            wire:loading.attr="aria-busy"
            wire:target="applyReportPeriod,resetReportPeriod,reportSearch,categoryFilter,stockStatus,exceptionFilter,sortBy,sortDirection,perPage,resetRegisterFilters,gotoPage,previousPage,nextPage"
        >
            <div
                data-kitchen-production-report-results
                class="space-y-6 transition-opacity duration-200"
                wire:loading.class="pointer-events-none opacity-60"
                wire:target="applyReportPeriod,resetReportPeriod,reportSearch,categoryFilter,stockStatus,exceptionFilter,sortBy,sortDirection,perPage,resetRegisterFilters,gotoPage,previousPage,nextPage"
            >
        @if ($report['total_rows'] === 0)
        <x-filament::section>
            <div
                data-kitchen-production-unconfigured-state
                role="status"
                aria-label="Kitchen production tracking is not configured"
                class="flex flex-col items-center px-4 py-12 text-center sm:py-16"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-300">
                    <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-7 w-7" />
                </span>
                <h2 class="mt-5 text-lg font-bold tracking-tight text-gray-950 dark:text-white">Production tracking is not configured</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600 dark:text-gray-300">No menu items are configured to track kitchen production. Enable production tracking and define a production unit before using this report.</p>

                @if (\App\Filament\Admin\Resources\MenuItems\MenuItemResource::canViewAny())
                    <a
                        href="{{ \App\Filament\Admin\Resources\MenuItems\MenuItemResource::getUrl() }}"
                        class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                    >
                        Manage menu items
                        <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" />
                    </a>
                @else
                    <p class="mt-4 max-w-xl text-xs leading-5 text-gray-500 dark:text-gray-400">Ask a manager or administrator to enable kitchen production tracking on the appropriate menu items.</p>
                @endif
            </div>
        </x-filament::section>
        @else
        <section aria-label="Kitchen production overview">
            @livewire(\App\Filament\Admin\Widgets\KitchenProductionReportStats::class, ['summary' => $report['summary'], 'fromDate' => $this->startDate, 'untilDate' => $this->endDate], key('kitchen-production-report-stats-'.$this->startDate.'-'.$this->endDate))
        </section>

        @if ($report['has_period_activity'])
        <x-filament::section heading="Production register" description="Filter, sort, and review finished-food performance for the selected reporting period.">
            <div data-kitchen-production-register-filters class="kitchen-production-register-filters mb-6 rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Filter production register</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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

                    <label for="kitchen-report-exception" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Exceptions
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input.select id="kitchen-report-exception" wire:model.live="exceptionFilter" wire:loading.attr="disabled">
                                <option value="">All items</option>
                                @foreach ($this->exceptionFilterOptions() as $value => $label)
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
                        <p wire:loading wire:target="reportSearch,categoryFilter,stockStatus,exceptionFilter,sortBy,sortDirection,perPage,resetRegisterFilters,gotoPage,previousPage,nextPage" class="mt-1 text-xs font-medium text-primary-600 dark:text-primary-400">Updating production register&hellip;</p>
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

            <div data-kitchen-production-mobile-register class="kitchen-production-mobile-register grid gap-4 lg:grid-cols-2 2xl:hidden" aria-label="Production report cards">
                @forelse ($report['rows'] as $row)
                    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                        @php($productionBatchesUrl = $this->productionBatchesUrl($row))
                        @php($restaurantOrdersUrl = $this->restaurantOrdersUrl($row))

                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-950 dark:text-white">{{ $row['name'] }}</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['category'] }} · {{ number_format($row['usage_per_sale'], 3) }} {{ $row['unit'] }} per sale</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <x-filament::badge :color="match ($row['status']) { 'healthy' => 'success', 'low' => 'warning', 'negative' => 'danger' }">
                                    {{ match ($row['status']) { 'healthy' => 'Healthy', 'low' => 'Low balance', 'negative' => 'Below zero' } }}
                                </x-filament::badge>
                                <p class="mt-1 text-[0.6875rem] font-medium text-gray-500 dark:text-gray-400">Threshold: {{ number_format($row['low_stock_threshold'], 3) }} {{ $row['unit'] }}</p>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 overflow-hidden rounded-lg border border-gray-200 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-200">
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

                        @if ($productionBatchesUrl || $restaurantOrdersUrl)
                            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-gray-200 pt-4 text-sm font-semibold dark:border-white/10">
                                @if ($productionBatchesUrl)
                                    <a data-kitchen-production-batches-link href="{{ $productionBatchesUrl }}" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                        View batches
                                        <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" />
                                    </a>
                                @endif
                                @if ($restaurantOrdersUrl)
                                    <a data-kitchen-production-orders-link href="{{ $restaurantOrdersUrl }}" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                        View orders
                                        <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" />
                                    </a>
                                @endif
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        {{ $report['total_rows'] === 0 ? 'No production-tracked menu items found.' : 'No menu items match the current register filters.' }}
                    </p>
                @endforelse
            </div>

            <div data-kitchen-production-desktop-register class="kitchen-production-desktop-register hidden 2xl:block">
                <div
                    data-kitchen-production-table-scroll
                    role="region"
                    tabindex="0"
                    aria-label="Production report table; scroll horizontally to review all metrics."
                    class="kitchen-production-table-scroll overflow-x-auto rounded-lg border border-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-inset dark:border-gray-700"
                >
                    <table class="w-full min-w-[1450px] border-separate border-spacing-0 text-left text-sm">
                        <caption class="sr-only">Kitchen production, sales, and closing finished-food balance report</caption>
                        <thead class="text-xs uppercase">
                            <tr class="text-[0.6875rem] font-semibold tracking-wider">
                                <th scope="colgroup" colspan="2" class="border-b border-r-2 border-gray-300 bg-slate-100 px-4 py-2.5 text-slate-700 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-200">Item &amp; stock</th>
                                <th scope="colgroup" colspan="3" class="border-b border-r-2 border-sky-200 bg-sky-50 px-4 py-2.5 text-right text-sky-700 dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-300">Production</th>
                                <th scope="colgroup" colspan="2" class="border-b border-r-2 border-emerald-200 bg-emerald-50 px-4 py-2.5 text-right text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">Sales</th>
                                <th scope="colgroup" colspan="3" class="border-b border-r-2 border-amber-200 bg-amber-50 px-4 py-2.5 text-right text-amber-700 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300">Inventory balance</th>
                                <th scope="colgroup" colspan="2" class="border-b border-violet-200 bg-violet-50 px-4 py-2.5 text-right text-violet-700 dark:border-violet-800 dark:bg-violet-950/50 dark:text-violet-300">Performance</th>
                            </tr>
                            <tr>
                                <th scope="col" class="sticky left-0 z-30 w-64 min-w-64 border-b border-r border-gray-200 bg-gray-50 px-4 py-4 text-gray-600 shadow-[2px_0_0_0_rgb(229_231_235)] dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:shadow-[2px_0_0_0_rgb(55_65_81)]">Menu item</th>
                                <th scope="col" class="sticky left-64 z-30 w-36 min-w-36 border-b border-r-2 border-gray-300 bg-gray-50 px-4 py-4 text-gray-600 shadow-[2px_0_0_0_rgb(209_213_219)] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:shadow-[2px_0_0_0_rgb(75_85_99)]">Closing stock</th>
                                <th scope="col" class="border-b border-r border-gray-200 bg-sky-50/60 px-4 py-4 text-right whitespace-nowrap text-gray-600 dark:border-gray-700 dark:bg-sky-950/30 dark:text-gray-300">Produced</th>
                                <th scope="col" class="border-b border-r border-gray-200 bg-sky-50/60 px-4 py-4 text-right whitespace-nowrap text-gray-600 dark:border-gray-700 dark:bg-sky-950/30 dark:text-gray-300">Wasted</th>
                                <th scope="col" class="border-b border-r-2 border-gray-300 bg-sky-50/60 px-4 py-4 text-right whitespace-nowrap text-gray-600 dark:border-gray-600 dark:bg-sky-950/30 dark:text-gray-300">Net</th>
                                <th scope="col" class="border-b border-r border-gray-200 bg-emerald-50/60 px-4 py-4 text-right whitespace-nowrap text-gray-600 dark:border-gray-700 dark:bg-emerald-950/30 dark:text-gray-300">Units sold</th>
                                <th scope="col" class="border-b border-r-2 border-gray-300 bg-emerald-50/60 px-4 py-4 text-right whitespace-nowrap text-gray-600 dark:border-gray-600 dark:bg-emerald-950/30 dark:text-gray-300">Amount sold</th>
                                <th scope="col" class="border-b border-r border-gray-200 bg-amber-50/60 px-4 py-4 text-right whitespace-nowrap text-gray-600 dark:border-gray-700 dark:bg-amber-950/30 dark:text-gray-300">Opening balance</th>
                                <th scope="col" class="border-b border-r border-gray-200 bg-amber-50/60 px-4 py-4 text-right whitespace-nowrap text-gray-600 dark:border-gray-700 dark:bg-amber-950/30 dark:text-gray-300">Period variance</th>
                                <th scope="col" class="border-b border-r-2 border-gray-300 bg-amber-50/60 px-4 py-4 text-right whitespace-nowrap text-gray-600 dark:border-gray-600 dark:bg-amber-950/30 dark:text-gray-300">Closing balance</th>
                                <th scope="col" class="border-b border-r border-gray-200 bg-violet-50/60 px-4 py-4 text-right whitespace-nowrap text-gray-600 dark:border-gray-700 dark:bg-violet-950/30 dark:text-gray-300">Available-stock sell-through</th>
                                <th scope="col" class="border-b border-gray-200 bg-violet-50/60 px-4 py-4 text-right whitespace-nowrap text-gray-600 dark:border-gray-700 dark:bg-violet-950/30 dark:text-gray-300">Net revenue</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white text-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            @forelse ($report['rows'] as $row)
                                @php($productionBatchesUrl = $this->productionBatchesUrl($row))
                                @php($restaurantOrdersUrl = $this->restaurantOrdersUrl($row))
                                <tr class="group">
                                    <th scope="row" class="sticky left-0 z-20 w-64 min-w-64 border-b border-r border-gray-200 bg-white px-4 py-4 align-top shadow-[2px_0_0_0_rgb(229_231_235)] group-hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:shadow-[2px_0_0_0_rgb(55_65_81)] dark:group-hover:bg-gray-800">
                                        <p class="font-semibold text-gray-950 dark:text-white">{{ $row['name'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['category'] }} · {{ number_format($row['usage_per_sale'], 3) }} {{ $row['unit'] }} per sale</p>
                                        @if ($productionBatchesUrl || $restaurantOrdersUrl)
                                            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs font-semibold">
                                                @if ($productionBatchesUrl)
                                                    <a data-kitchen-production-batches-link href="{{ $productionBatchesUrl }}" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">View batches</a>
                                                @endif
                                                @if ($restaurantOrdersUrl)
                                                    <a data-kitchen-production-orders-link href="{{ $restaurantOrdersUrl }}" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">View orders</a>
                                                @endif
                                            </div>
                                        @endif
                                    </th>
                                    <td class="sticky left-64 z-20 w-36 min-w-36 border-b border-r-2 border-gray-300 bg-white px-4 py-4 align-top shadow-[2px_0_0_0_rgb(209_213_219)] group-hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:shadow-[2px_0_0_0_rgb(75_85_99)] dark:group-hover:bg-gray-800">
                                        <x-filament::badge :color="match ($row['status']) { 'healthy' => 'success', 'low' => 'warning', 'negative' => 'danger' }">
                                            {{ match ($row['status']) { 'healthy' => 'Healthy', 'low' => 'Low balance', 'negative' => 'Below zero' } }}
                                        </x-filament::badge>
                                        <p class="mt-1 text-[0.6875rem] font-medium text-gray-500 dark:text-gray-400">Threshold: {{ number_format($row['low_stock_threshold'], 3) }} {{ $row['unit'] }}</p>
                                    </td>
                                    <td class="border-b border-r border-gray-200 px-4 py-4 text-right align-top tabular-nums whitespace-nowrap dark:border-gray-700">{{ number_format($row['produced'], 3) }} {{ $row['unit'] }}</td>
                                    <td class="border-b border-r border-gray-200 px-4 py-4 text-right align-top tabular-nums whitespace-nowrap dark:border-gray-700">{{ number_format($row['wasted'], 3) }} {{ $row['unit'] }}</td>
                                    <td class="border-b border-r-2 border-gray-300 px-4 py-4 text-right align-top font-medium tabular-nums whitespace-nowrap dark:border-gray-600">{{ number_format($row['net_produced'], 3) }} {{ $row['unit'] }}</td>
                                    <td class="border-b border-r border-gray-200 px-4 py-4 text-right align-top tabular-nums whitespace-nowrap dark:border-gray-700">{{ number_format($row['sold_units']) }}</td>
                                    <td class="border-b border-r-2 border-gray-300 px-4 py-4 text-right align-top tabular-nums whitespace-nowrap dark:border-gray-600">{{ number_format($row['production_amount_sold'], 3) }} {{ $row['unit'] }}</td>
                                    <td class="border-b border-r border-gray-200 px-4 py-4 text-right align-top tabular-nums whitespace-nowrap dark:border-gray-700">{{ number_format($row['opening_balance'], 3) }} {{ $row['unit'] }}</td>
                                    <td class="border-b border-r border-gray-200 px-4 py-4 text-right align-top font-medium tabular-nums whitespace-nowrap dark:border-gray-700 {{ $row['period_variance'] < 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">{{ number_format($row['period_variance'], 3) }} {{ $row['unit'] }}</td>
                                    <td class="border-b border-r-2 border-gray-300 px-4 py-4 text-right align-top font-semibold tabular-nums whitespace-nowrap dark:border-gray-600 {{ $row['closing_balance'] < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-950 dark:text-white' }}">{{ number_format($row['closing_balance'], 3) }} {{ $row['unit'] }}</td>
                                    <td class="border-b border-r border-gray-200 px-4 py-4 text-right align-top tabular-nums whitespace-nowrap dark:border-gray-700">{{ $row['sell_through'] === null ? 'N/A' : number_format($row['sell_through'], 1).'%' }}</td>
                                    <td class="border-b border-gray-200 px-4 py-4 text-right align-top font-medium tabular-nums whitespace-nowrap dark:border-gray-700 {{ $row['net_revenue'] < 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">GHS {{ number_format($row['net_revenue'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="12" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">{{ $report['total_rows'] === 0 ? 'No production-tracked menu items found.' : 'No menu items match the current register filters.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($report['rows']->hasPages())
                <div data-kitchen-production-register-pagination class="kitchen-production-register-pagination mt-6 border-t border-gray-200 pt-4 dark:border-white/10">
                    {{ $report['rows']->links() }}
                </div>
            @endif
        </x-filament::section>
        @else
        <x-filament::section>
            <div
                data-kitchen-production-inactive-state
                role="status"
                aria-label="No kitchen production or sales activity for selected period"
                class="flex flex-col items-center px-4 py-12 text-center sm:py-16"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-7 w-7" />
                </span>
                <h2 class="mt-5 text-lg font-bold tracking-tight text-gray-950 dark:text-white">No production or sales activity in this period</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600 dark:text-gray-300">Tracked menu items exist, but no production, wastage, finished-food stock movements, payments, or refunds were recorded during {{ strtolower($this->periodLabel()) }}.</p>
                <p class="mt-1 max-w-xl text-sm text-gray-500 dark:text-gray-400">Opening balances remain reflected in the stock-health summary above.</p>
                <a
                    href="#kitchen-production-report-period-controls"
                    class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                >
                    Change reporting period
                    <x-filament::icon icon="heroicon-m-arrow-up" class="h-4 w-4" />
                </a>
            </div>
        </x-filament::section>
        @endif
        @endif
            </div>

            <div
                data-kitchen-production-report-loading-overlay
                role="status"
                aria-live="polite"
                aria-atomic="true"
                wire:loading.flex
                wire:target="applyReportPeriod,resetReportPeriod,reportSearch,categoryFilter,stockStatus,exceptionFilter,sortBy,sortDirection,perPage,resetRegisterFilters,gotoPage,previousPage,nextPage"
                class="absolute inset-0 z-40 items-start justify-center rounded-xl bg-white/75 px-4 py-12 backdrop-blur-[1px] dark:bg-gray-950/75"
                style="display: none;"
            >
                <div class="inline-flex items-center gap-3 rounded-xl border border-primary-200 bg-white px-4 py-3 font-medium text-primary-700 shadow-lg dark:border-primary-500/30 dark:bg-gray-900 dark:text-primary-300">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 animate-spin" />
                    <span>Updating kitchen production report&hellip;</span>
                </div>
            </div>
        </section>
    </div>

    <style>
        @media print {
            @page {
                size: landscape;
                margin: 12mm;
            }

            .fi-sidebar,
            .fi-topbar,
            .fi-header,
            .fi-breadcrumbs,
            #kitchen-production-report-period-controls,
            .kitchen-production-register-filters,
            [data-kitchen-production-report-loading-overlay],
            .kitchen-production-mobile-register,
            .kitchen-production-register-pagination {
                display: none !important;
            }

            .fi-main-ctn {
                margin-inline-start: 0 !important;
                min-height: auto !important;
            }

            .fi-main {
                max-width: none !important;
                padding: 0 !important;
            }

            [data-kitchen-production-report-page] {
                gap: 1rem !important;
            }

            [data-kitchen-production-report-page] .fi-section,
            [data-kitchen-production-report-page] article,
            [data-kitchen-production-report-page] tr {
                break-inside: avoid;
            }

            .kitchen-production-desktop-register {
                display: block !important;
            }

            .kitchen-production-table-scroll {
                overflow: visible !important;
            }

            .kitchen-production-desktop-register table {
                min-width: 0 !important;
            }

            .kitchen-production-desktop-register .sticky {
                position: static !important;
            }

            [data-kitchen-production-report-page] a {
                color: inherit !important;
                text-decoration: none !important;
            }
        }
    </style>
</x-filament-panels::page>
