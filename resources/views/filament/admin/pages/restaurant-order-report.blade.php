<x-filament::page>
    @php($report = $this->getReportData())

    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Restaurant operations</p>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight">Order performance</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">Review food-order volume, payment performance, outstanding balances, and kitchen activity for the selected period.</p>
                </div>

                <div class="w-full lg:max-w-xs">
                    <label for="restaurant-report-period" class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Report period</label>
                    <select id="restaurant-report-period" wire:model.live="period" class="fi-input w-full">
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                        <option value="this_month">This Month</option>
                        <option value="this_year">This Year</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
            </div>
        </x-filament::section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Orders received</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($report['totalOrders']) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ number_format($report['totalItems']) }} item(s) ordered</p>
            </x-filament::section>

            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paid revenue</p>
                <p class="mt-2 text-3xl font-bold text-success-600 dark:text-success-400">GHS {{ number_format($report['revenue'], 2) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ number_format($report['paidOrders']) }} paid order(s)</p>
            </x-filament::section>

            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Outstanding balance</p>
                <p class="mt-2 text-3xl font-bold text-warning-600 dark:text-warning-400">GHS {{ number_format($report['outstanding'], 2) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ number_format($report['pendingOrders']) }} pending payment(s)</p>
            </x-filament::section>

            <x-filament::section compact>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average paid order</p>
                <p class="mt-2 text-3xl font-bold text-primary-600 dark:text-primary-400">GHS {{ number_format($report['averageOrderValue'], 2) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $report['paymentRate'] }}% payment completion</p>
            </x-filament::section>
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
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
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
                            <tr class="align-top">
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
                                <td class="px-4 py-4 text-xs text-gray-600 dark:text-gray-300">{{ $order->created_at?->format('M d, Y') }}<span class="mt-1 block text-gray-500 dark:text-gray-400">{{ $order->created_at?->format('g:i A') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">No restaurant orders found for {{ strtolower($this->periodLabel()) }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
