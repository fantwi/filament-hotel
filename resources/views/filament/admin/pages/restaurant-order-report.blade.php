<x-filament::page>
    @php
        $report = $this->getReportData();
    @endphp
    <div class="space-y-6">
        <x-filament::section>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Restaurant operations</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">Order performance</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Review period-based food-order and payment performance alongside the current kitchen queue.</p>
            </div>
            <x-filament.report-period-controls id="restaurant-report-period-controls" class="mt-5" />
            <label for="restaurant-report-per-page" class="mt-4 block max-w-xs text-sm font-semibold text-gray-700 dark:text-gray-200">
                Rows per page
                <select
                    id="restaurant-report-per-page"
                    wire:model.live="perPage"
                    wire:loading.attr="disabled"
                    wire:target="perPage"
                    class="fi-input mt-1 w-full"
                >
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </label>
        </x-filament::section>

        <section
            aria-label="Restaurant report results"
            class="relative"
            wire:loading.attr="aria-busy"
            wire:target="applyReportPeriod,resetReportPeriod,perPage,registerSearch,paymentStatus,fulfillmentStatus,orderingChannel,resetRegisterFilters,gotoPage,previousPage,nextPage"
        >
            <div
                data-restaurant-report-results
                class="space-y-6 transition-opacity duration-200"
                wire:loading.class="pointer-events-none opacity-60"
                wire:target="applyReportPeriod,resetReportPeriod,perPage,registerSearch,paymentStatus,fulfillmentStatus,orderingChannel,resetRegisterFilters,gotoPage,previousPage,nextPage"
            >
        @if ($report['hasPeriodActivity'])
        <section aria-label="Restaurant order overview">
            @livewire(\App\Filament\Admin\Widgets\RestaurantOrderReportStats::class, [
                'reportData' => [
                    'totalOrders' => $report['totalOrders'],
                    'totalItems' => $report['totalItems'],
                    'revenue' => $report['revenue'],
                    'refunds' => $report['refunds'],
                    'netRevenue' => $report['netRevenue'],
                    'outstanding' => $report['outstanding'],
                    'averageOrderValue' => $report['averageOrderValue'],
                    'paymentRate' => $report['paymentRate'],
                ],
                'reportPeriodLabel' => $this->periodLabel(),
            ], key('restaurant-order-report-stats-'.$this->period.'-'.$this->startDate.'-'.$this->endDate))
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <x-filament::section heading="Payment status" description="{{ $this->periodLabel() }} at a glance">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"><span class="h-2.5 w-2.5 rounded-full bg-success-500"></span>Paid orders</dt>
                        <dd class="font-bold">{{ number_format($report['paidOrders']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"><span class="h-2.5 w-2.5 rounded-full bg-warning-500"></span>Awaiting payment</dt>
                        <dd class="font-bold">{{ number_format($report['pendingOrders']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"><span class="h-2.5 w-2.5 rounded-full bg-danger-500"></span>Cancelled</dt>
                        <dd class="font-bold">{{ number_format($report['cancelledOrders']) }}</dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section heading="Live kitchen queue" description="Current active orders across all order dates" class="lg:col-span-2">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-4xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($report['liveKitchenOrders']) }}</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Paid or corporate orders that are confirmed, preparing, or ready to serve</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-3 text-sm dark:bg-white/5">
                        <p class="font-semibold">Live operational snapshot</p>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">Not affected by the selected report period</p>
                    </div>
                </div>
            </x-filament::section>
        </section>

        <x-filament::section heading="Order register" description="Individual restaurant orders for {{ strtolower($this->periodLabel()) }}.">
            <div data-restaurant-register-filters class="mb-6 rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <label for="restaurant-register-search" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Search orders
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input
                                id="restaurant-register-search"
                                type="search"
                                wire:model.live.debounce.400ms="registerSearch"
                                maxlength="100"
                                placeholder="Order number, guest, or email"
                            />
                        </x-filament::input.wrapper>
                    </label>

                    <label for="restaurant-payment-status" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Payment status
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input.select id="restaurant-payment-status" wire:model.live="paymentStatus">
                                <option value="">All payment statuses</option>
                                @foreach ($this->paymentStatusOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>

                    <label for="restaurant-fulfillment-status" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Fulfilment status
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input.select id="restaurant-fulfillment-status" wire:model.live="fulfillmentStatus">
                                <option value="">All fulfilment statuses</option>
                                @foreach ($this->fulfillmentStatusOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>

                    <label for="restaurant-ordering-channel" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Ordering channel
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input.select id="restaurant-ordering-channel" wire:model.live="orderingChannel">
                                <option value="">All ordering channels</option>
                                @foreach ($this->orderingChannelOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>
                </div>

                <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                    <div>
                        <p data-restaurant-register-summary aria-live="polite" class="text-sm font-semibold text-gray-950 dark:text-white">
                            Showing {{ number_format($report['orders']->total()) }} of {{ number_format($report['totalOrders']) }} orders
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Register filters do not change the period overview metrics.</p>
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

            <div class="space-y-4 md:hidden" aria-label="Order cards">
                @forelse ($report['orders'] as $order)
                    @php
                        $orderDetailsUrl = $this->orderDetailsUrl($order);
                        $statusColor = match ($order->status) {
                            'confirmed' => 'info',
                            'preparing' => 'warning',
                            'ready', 'served' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        };
                        $paymentColor = $order->payment_status === 'completed' ? 'success' : ($order->payment_status === 'pending' ? 'warning' : 'gray');
                    @endphp
                    <article class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="break-all font-semibold text-gray-950 dark:text-white">
                                    @if ($orderDetailsUrl)
                                        <a data-restaurant-order-link href="{{ $orderDetailsUrl }}" class="inline-flex items-center gap-1 hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:text-primary-300">
                                            {{ $order->order_number }}
                                            <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4 shrink-0" />
                                        </a>
                                    @else
                                        {{ $order->order_number }}
                                    @endif
                                </p>
                                <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $order->guest?->full_name ?: $order->customer_email ?: 'Walk-in guest' }}</p>
                            </div>
                            <p class="whitespace-nowrap text-sm font-bold text-primary-600 dark:text-primary-400">GHS {{ number_format($order->total, 2) }}</p>
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-white/10">
                            <div><dt class="text-xs text-gray-500 dark:text-gray-400">Items</dt><dd class="mt-1 font-medium">{{ number_format($order->items_sum_quantity ?? 0) }}</dd></div>
                            <div><dt class="text-xs text-gray-500 dark:text-gray-400">Channel</dt><dd class="mt-1 capitalize font-medium">{{ $order->ordering_channel ?: 'web' }}</dd></div>
                            <div><dt class="text-xs text-gray-500 dark:text-gray-400">Fulfillment</dt><dd class="mt-1"><x-filament::badge :color="$statusColor">{{ str_replace('_', ' ', ucfirst($order->status)) }}</x-filament::badge></dd></div>
                            <div><dt class="text-xs text-gray-500 dark:text-gray-400">Payment</dt><dd class="mt-1"><x-filament::badge :color="$paymentColor">{{ str_replace('_', ' ', ucfirst($order->payment_status)) }}</x-filament::badge></dd></div>
                        </dl>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Created {{ $order->created_at?->format('M d, Y · g:i A') }}</p>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">No restaurant orders found for {{ strtolower($this->periodLabel()) }}.</div>
                @endforelse
            </div>

            <div class="hidden md:block">
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
                    <table class="w-full min-w-[760px] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                        <caption class="sr-only">Restaurant order register</caption>
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">Order and guest</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Items and channel</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Fulfillment</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Payment</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Total</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @forelse ($report['orders'] as $order)
                                @php
                                    $orderDetailsUrl = $this->orderDetailsUrl($order);
                                    $statusColor = match ($order->status) {
                                        'confirmed' => 'info',
                                        'preparing' => 'warning',
                                        'ready', 'served' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    };
                                    $paymentColor = $order->payment_status === 'completed' ? 'success' : ($order->payment_status === 'pending' ? 'warning' : 'gray');
                                @endphp
                                <tr class="align-top hover:bg-gray-50 dark:hover:bg-white/5">
                                    <th scope="row" class="px-4 py-4">
                                        <p class="font-semibold">
                                            @if ($orderDetailsUrl)
                                                <a data-restaurant-order-link href="{{ $orderDetailsUrl }}" class="inline-flex items-center gap-1 hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:text-primary-300">
                                                    {{ $order->order_number }}
                                                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4 shrink-0" />
                                                </a>
                                            @else
                                                {{ $order->order_number }}
                                            @endif
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $order->guest?->full_name ?: $order->customer_email ?: 'Walk-in guest' }}</p>
                                    </th>
                                    <td class="px-4 py-4">
                                        <p class="font-medium">{{ number_format($order->items_sum_quantity ?? 0) }} item(s)</p>
                                        <p class="mt-1 text-xs capitalize text-gray-500 dark:text-gray-400">{{ $order->ordering_channel ?: 'web' }} order</p>
                                    </td>
                                    <td class="px-4 py-4"><x-filament::badge :color="$statusColor">{{ str_replace('_', ' ', ucfirst($order->status)) }}</x-filament::badge></td>
                                    <td class="px-4 py-4"><x-filament::badge :color="$paymentColor">{{ str_replace('_', ' ', ucfirst($order->payment_status)) }}</x-filament::badge></td>
                                    <td class="px-4 py-4 text-right font-semibold">GHS {{ number_format($order->total, 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-xs text-gray-600 dark:text-gray-300">{{ $order->created_at?->format('M d, Y') }}<span class="mt-1 block text-gray-500 dark:text-gray-400">{{ $order->created_at?->format('g:i A') }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">No restaurant orders found for {{ strtolower($this->periodLabel()) }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($report['orders']->hasPages())
                <div class="mt-6 border-t border-gray-200 pt-4 dark:border-white/10">
                    {{ $report['orders']->links() }}
                </div>
            @endif
        </x-filament::section>
        @else
        <x-filament::section>
            <div
                data-restaurant-report-empty-state
                role="status"
                aria-label="No restaurant activity for selected period"
                class="flex flex-col items-center px-4 py-12 text-center sm:py-16"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-7 w-7" />
                </span>
                <h2 class="mt-5 text-lg font-bold tracking-tight text-gray-950 dark:text-white">No restaurant activity in this period</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600 dark:text-gray-300">No food orders were created and no restaurant payments or refunds were recorded during {{ strtolower($this->periodLabel()) }}.</p>

                @if ($report['liveKitchenOrders'] > 0)
                    <div data-restaurant-live-queue-note role="note" class="mt-5 flex max-w-xl items-start gap-3 rounded-xl border border-warning-200 bg-warning-50 px-4 py-3 text-left dark:border-warning-500/20 dark:bg-warning-500/10">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300">
                            <x-filament::icon icon="heroicon-o-fire" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ number_format($report['liveKitchenOrders']) }} {{ $report['liveKitchenOrders'] === 1 ? 'active order is' : 'active orders are' }} currently in the live kitchen queue
                            </p>
                            <p class="mt-1 text-xs leading-5 text-gray-600 dark:text-gray-300">This live operational work falls outside the selected reporting period.</p>
                        </div>
                    </div>
                @endif

                <a
                    href="#restaurant-report-period-controls"
                    class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                >
                    Change reporting period
                    <x-filament::icon icon="heroicon-m-arrow-up" class="h-4 w-4" />
                </a>
            </div>
        </x-filament::section>
        @endif
            </div>

            <div
                data-restaurant-report-loading-overlay
                role="status"
                aria-live="polite"
                aria-atomic="true"
                wire:loading.flex
                wire:target="applyReportPeriod,resetReportPeriod,perPage,registerSearch,paymentStatus,fulfillmentStatus,orderingChannel,resetRegisterFilters,gotoPage,previousPage,nextPage"
                class="absolute inset-0 z-10 items-start justify-center rounded-xl bg-white/75 px-4 py-12 backdrop-blur-[1px] dark:bg-gray-950/75"
                style="display: none;"
            >
                <div class="inline-flex items-center gap-3 rounded-xl border border-primary-200 bg-white px-4 py-3 font-medium text-primary-700 shadow-lg dark:border-primary-500/30 dark:bg-gray-900 dark:text-primary-300">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 animate-spin" />
                    <span>Updating restaurant report&hellip;</span>
                </div>
            </div>
        </section>
    </div>
</x-filament::page>
