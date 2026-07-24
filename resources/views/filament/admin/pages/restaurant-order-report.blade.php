<x-filament::page>
    @php($report = $this->getReportData())

    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Report period</x-slot>

            <select wire:model.live="period" class="fi-input w-full max-w-sm">
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="this_year">This Year</option>
                <option value="all">All Time</option>
            </select>
        </x-filament::section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                'totalOrders' => ['Orders', 'text-gray-950 dark:text-white'],
                'revenue' => ['Paid Revenue', 'text-success-600 dark:text-success-400'],
                'outstanding' => ['Outstanding', 'text-warning-600 dark:text-warning-400'],
                'averageOrderValue' => ['Average Order', 'text-primary-600 dark:text-primary-400'],
            ] as $key => [$label, $colour])
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="mt-2 text-2xl font-bold {{ $colour }}">
                        @if (in_array($key, ['revenue', 'outstanding', 'averageOrderValue'], true)) GHS @endif{{ number_format($report[$key], 2) }}
                    </div>
                </x-filament::section>
            @endforeach
        </div>

        <x-filament::section heading="Orders">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs uppercase text-gray-500 dark:border-white/10">
                        <tr>
                            <th class="px-3 py-3">Order</th>
                            <th class="px-3 py-3">Guest</th>
                            <th class="px-3 py-3">Items</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Payment</th>
                            <th class="px-3 py-3">Total</th>
                            <th class="px-3 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-white/10">
                        @forelse ($report['orders'] as $order)
                            <tr>
                                <td class="px-3 py-3 font-medium">{{ $order->order_number }}</td>
                                <td class="px-3 py-3">{{ $order->guest?->full_name ?: 'Walk-in Guest' }}</td>
                                <td class="px-3 py-3">{{ $order->items->sum('quantity') }}</td>
                                <td class="px-3 py-3">{{ ucfirst($order->status) }}</td>
                                <td class="px-3 py-3">{{ ucfirst($order->payment_status) }}</td>
                                <td class="px-3 py-3 font-semibold">GHS {{ number_format($order->total, 2) }}</td>
                                <td class="px-3 py-3">{{ $order->created_at?->format('M d, Y g:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-10 text-center text-gray-500">No restaurant orders found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
