<x-filament::page>
    @php($receivables = $this->receivables())

    <x-filament::section heading="Corporate receivables" description="Record a payment received by the accountant outside the online checkout. Clearing a balance updates the guest dashboard immediately.">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th class="px-3 py-3">Transaction</th><th class="px-3 py-3">Organisation</th><th class="px-3 py-3">Guest</th><th class="px-3 py-3">Amount</th><th class="px-3 py-3">Created</th><th class="px-3 py-3">Clear payment</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($receivables as $item)
                        <tr>
                            <td class="px-3 py-4 font-medium">{{ $item['label'] }} #{{ $item['id'] }}</td>
                            <td class="px-3 py-4">{{ $item['organization'] }}</td>
                            <td class="px-3 py-4">{{ $item['guest'] }}</td>
                            <td class="px-3 py-4 font-semibold">GHS {{ number_format($item['amount'], 2) }}</td>
                            <td class="px-3 py-4">{{ $item['created_at']?->format('M d, Y') }}</td>
                            <td class="px-3 py-4">
                                <form method="POST" action="{{ route('admin.corporate-receivables.pay', [$item['type'], $item['id']]) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="method" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                                        <option value="cash">Cash</option><option value="momo">Mobile money</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option>
                                    </select>
                                    <input name="transaction_reference" class="w-36 rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900" placeholder="Reference (optional)">
                                    <button type="submit" class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500">Mark paid</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-gray-500">No unpaid corporate transactions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament::page>
