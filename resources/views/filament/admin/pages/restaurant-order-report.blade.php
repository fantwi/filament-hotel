<x-filament::page>
    @php
        $report = $this->getReportData();
    @endphp
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Restaurant operations</p>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight">Order performance</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Review food-order volume, payment performance, outstanding balances, and kitchen activity for the selected period.</p>
                </div>

                <div class="grid w-full gap-4 sm:grid-cols-2 lg:max-w-xl">
                    <div>
                    <label for="restaurant-report-period" class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Report period</label>
                    <select id="restaurant-report-period" wire:model.live="period" class="fi-input w-full">
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                        <option value="this_month">This Month</option>
                        <option value="this_year">This Year</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
                    <div>
                        <label for="restaurant-report-per-page" class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Rows per page</label>
                        <select id="restaurant-report-per-page" wire:model.live="perPage" class="fi-input w-full">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <section aria-label="Restaurant order overview">
            @livewire(\App\Filament\Admin\Widgets\RestaurantOrderReportStats::class, ['period' => $this->period], key('restaurant-order-report-stats-'.$this->period))
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

            <x-filament::section heading="Kitchen activity" description="Orders currently in the fulfillment flow" class="lg:col-span-2">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-4xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($report['activeOrders']) }}</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Confirmed, preparing, or ready to serve</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-3 text-sm dark:bg-white/5">
                        <p class="font-semibold">Selected period</p>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $this->periodLabel() }}</p>
                    </div>
                </div>
            </x-filament::section>
        </section>

        <x-filament::section heading="Order register" description="Individual restaurant orders for {{ strtolower($this->periodLabel()) }}.">
            <div class="space-y-4 md:hidden" aria-label="Order cards">
                @forelse ($report['orders'] as $order)
                    @php
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
                                <p class="truncate font-semibold text-gray-950 dark:text-white">{{ $order->order_number }}</p>
                                <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $order->guest?->full_name ?: $order->customer_email ?: 'Walk-in guest' }}</p>
                            </div>
                            <p class="whitespace-nowrap text-sm font-bold text-primary-600 dark:text-primary-400">GHS {{ number_format($order->total, 2) }}</p>
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-white/10">
                            <div><dt class="text-xs text-gray-500 dark:text-gray-400">Items</dt><dd class="mt-1 font-medium">{{ number_format($order->items->sum('quantity')) }}</dd></div>
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
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Order and guest</th>
                                <th class="px-4 py-3 font-semibold">Items and channel</th>
                                <th class="px-4 py-3 font-semibold">Fulfillment</th>
                                <th class="px-4 py-3 font-semibold">Payment</th>
                                <th class="px-4 py-3 text-right font-semibold">Total</th>
                                <th class="px-4 py-3 font-semibold">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @forelse ($report['orders'] as $order)
                                @php
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
                                    <td class="px-4 py-4">
                                        <p class="font-semibold">{{ $order->order_number }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $order->guest?->full_name ?: $order->customer_email ?: 'Walk-in guest' }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="font-medium">{{ number_format($order->items->sum('quantity')) }} item(s)</p>
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
    </div>
</x-filament::page>
