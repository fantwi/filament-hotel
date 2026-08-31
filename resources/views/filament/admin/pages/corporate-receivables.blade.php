<x-filament::page>
    @php
        $receivables = $this->paginatedReceivables();
        $summary = $this->summary();
        $typeLabels = [
            'booking' => 'Room bookings',
            'conference' => 'Conference bookings',
            'reservation' => 'Table reservations',
            'order' => 'Food orders',
        ];
    @endphp

    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-300" role="status">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-800 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300" role="alert">{{ session('error') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 px-5 py-6 text-white shadow-sm sm:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-100">Finance</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Corporate receivables</h1>
                    <p class="mt-2 text-sm leading-6 text-primary-100 sm:text-base">Track credit transactions and record offline payments received by your finance team.</p>
                </div>
                <div class="rounded-xl bg-white/10 px-4 py-3 text-sm ring-1 ring-white/15">
                    <span class="block text-primary-100">Settlement workflow</span>
                    <span class="mt-1 block font-semibold">Choose a method, then mark paid</span>
                </div>
            </div>
        </section>

        <x-filament::section heading="Receivables filters" description="Narrow the queue before recording a settlement. Summary cards and service breakdowns follow the selected filters.">
            <form wire:submit="applyFilters" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="xl:col-span-2">
                    <label for="receivables-search" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Search</label>
                    <input id="receivables-search" wire:model.live.debounce.400ms="search" type="search" placeholder="Organisation, guest, or service" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-900">
                </div>

                <div>
                    <label for="receivables-type" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Service</label>
                    <select id="receivables-type" wire:model.live="transactionType" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <option value="all">All services</option>
                        @foreach ($typeLabels as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="receivables-organization" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Organisation</label>
                    <select id="receivables-organization" wire:model.live="organizationId" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <option value="">All organisations</option>
                        @foreach ($this->organizationOptions() as $organizationId => $organizationName)
                            <option value="{{ $organizationId }}">{{ $organizationName }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="receivables-per-page" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Rows per page</label>
                    <select id="receivables-per-page" wire:model.live="perPage" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div>
                    <label for="receivables-from" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Created from</label>
                    <input id="receivables-from" wire:model.live="fromDate" type="date" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-900">
                </div>

                <div>
                    <label for="receivables-until" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Created until</label>
                    <input id="receivables-until" wire:model.live="untilDate" type="date" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-900">
                </div>

                <div class="flex items-end gap-2 md:col-span-2 xl:col-span-6">
                    <x-filament::button type="submit">Apply filters</x-filament::button>
                    <x-filament::button type="button" color="gray" wire:click="clearFilters">Clear</x-filament::button>
                </div>
            </form>
            @error('fromDate')<p class="mt-3 text-sm text-danger-600">{{ $message }}</p>@enderror
            @error('untilDate')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
        </x-filament::section>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Receivables summary">
            <x-filament::section compact>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Outstanding balance</p>
                    <x-filament::icon icon="heroicon-o-banknotes" class="h-5 w-5 text-danger-500" />
                </div>
                <p class="mt-3 text-2xl font-bold text-gray-950 dark:text-white">GHS {{ number_format($summary['total'], 2) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Across all active corporate accounts</p>
            </x-filament::section>
            <x-filament::section compact>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Open transactions</p>
                    <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-5 w-5 text-warning-500" />
                </div>
                <p class="mt-3 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($summary['count']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Awaiting full settlement</p>
            </x-filament::section>
            <x-filament::section compact>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Corporate accounts</p>
                    <x-filament::icon icon="heroicon-o-building-office-2" class="h-5 w-5 text-primary-500" />
                </div>
                <p class="mt-3 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($summary['organizations']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">With an outstanding transaction</p>
            </x-filament::section>
            <x-filament::section compact>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Receivable details</p>
                    <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-success-500" />
                </div>
                <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-gray-600 dark:text-gray-300">
                    @foreach ($typeLabels as $type => $label)
                        <span>{{ $label }}</span><span class="text-right font-semibold">{{ $summary['by_type'][$type] ?? 0 }}</span>
                    @endforeach
                </div>
            </x-filament::section>
        </section>

        <x-filament::section heading="Transactions awaiting payment" description="Record the remaining balance received through cash, mobile money, card, or bank transfer.">
            <div class="mb-6">
                <h2 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Receivables by service</h2>
                @livewire(\App\Filament\Admin\Widgets\CorporateReceivablesStats::class, ['filters' => [
                    'transaction_type' => $this->transactionType,
                    'search' => $this->search,
                    'organization_id' => $this->organizationId,
                    'from_date' => $this->fromDate,
                    'until_date' => $this->untilDate,
                ]], key('corporate-receivables-stats-'.$this->transactionType.'-'.$this->organizationId.'-'.$this->fromDate.'-'.$this->untilDate.'-'.md5($this->search)))
            </div>

            @if ($receivables->total() === 0)
                <div class="rounded-xl border border-dashed border-gray-300 px-5 py-12 text-center dark:border-gray-700">
                    <x-filament::icon icon="heroicon-o-check-circle" class="mx-auto h-10 w-10 text-success-500" />
                    <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">No unpaid corporate transactions</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">New credit bookings and orders will appear here.</p>
                </div>
            @else
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-3 font-semibold">Transaction</th>
                                <th class="px-3 py-3 font-semibold">Organisation</th>
                                <th class="px-3 py-3 font-semibold">Guest</th>
                                <th class="px-3 py-3 text-right font-semibold">Outstanding</th>
                                <th class="px-3 py-3 font-semibold">Created</th>
                                <th class="px-3 py-3 font-semibold">Clear payment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($receivables as $item)
                                <tr class="align-top hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-3 py-4 font-semibold text-gray-950 dark:text-white">{{ $item['label'] }} #{{ $item['id'] }}<span class="mt-1 block text-xs font-normal text-gray-500">{{ ucfirst($item['status']) }}</span></td>
                                    <td class="px-3 py-4 text-gray-700 dark:text-gray-300">{{ $item['organization'] }}</td>
                                    <td class="px-3 py-4 text-gray-700 dark:text-gray-300">{{ $item['guest'] }}</td>
                                    <td class="px-3 py-4 text-right font-bold text-danger-600 dark:text-danger-400">GHS {{ number_format($item['amount'], 2) }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-gray-600 dark:text-gray-400">{{ $item['created_at']?->format('M d, Y') }}</td>
                                    <td class="px-3 py-4">
                                        <form method="POST" action="{{ route('admin.corporate-receivables.pay', [$item['type'], $item['id']]) }}" class="flex min-w-[24rem] flex-wrap items-center gap-2">
                                            @csrf
                                            <label class="sr-only" for="method-{{ $item['type'] }}-{{ $item['id'] }}">Payment method</label>
                                            <select id="method-{{ $item['type'] }}-{{ $item['id'] }}" name="method" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                                                <option value="cash">Cash</option><option value="momo">Mobile money</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option>
                                            </select>
                                            <label class="sr-only" for="reference-{{ $item['type'] }}-{{ $item['id'] }}">Transaction reference</label>
                                            <input id="reference-{{ $item['type'] }}-{{ $item['id'] }}" name="transaction_reference" class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900" placeholder="Reference (optional)">
                                            <button type="submit" class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500">Mark paid</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-4 md:hidden">
                    @foreach ($receivables as $item)
                        <article class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-gray-950 dark:text-white">{{ $item['label'] }} #{{ $item['id'] }}</h3>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item['organization'] }} · {{ $item['guest'] }}</p>
                                </div>
                                <span class="whitespace-nowrap text-sm font-bold text-danger-600 dark:text-danger-400">GHS {{ number_format($item['amount'], 2) }}</span>
                            </div>
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Created {{ $item['created_at']?->format('M d, Y') }} · {{ ucfirst($item['status']) }}</p>
                            <form method="POST" action="{{ route('admin.corporate-receivables.pay', [$item['type'], $item['id']]) }}" class="mt-4 space-y-2">
                                @csrf
                                <label class="sr-only" for="mobile-method-{{ $item['type'] }}-{{ $item['id'] }}">Payment method</label>
                                <select id="mobile-method-{{ $item['type'] }}-{{ $item['id'] }}" name="method" class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                                    <option value="cash">Cash</option><option value="momo">Mobile money</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option>
                                </select>
                                <label class="sr-only" for="mobile-reference-{{ $item['type'] }}-{{ $item['id'] }}">Transaction reference</label>
                                <input id="mobile-reference-{{ $item['type'] }}-{{ $item['id'] }}" name="transaction_reference" class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900" placeholder="Reference (optional)">
                                <button type="submit" class="w-full rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500">Mark paid</button>
                            </form>
                        </article>
                    @endforeach
                </div>

                @if ($receivables->hasPages())
                    <div class="mt-6 border-t border-gray-200 pt-4 dark:border-white/10">
                        {{ $receivables->links() }}
                    </div>
                @endif
            @endif
        </x-filament::section>
    </div>
</x-filament::page>
