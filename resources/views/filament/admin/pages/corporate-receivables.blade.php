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
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Credit settlement overview</h2>
                    <p class="mt-2 text-sm leading-6 text-primary-100 sm:text-base">Review outstanding balances and record offline payments received by your finance team.</p>
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
                    <x-filament::input.wrapper>
                        <x-filament::input id="receivables-search" wire:model="draftSearch" type="search" placeholder="Organisation, guest, or service" />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label for="receivables-type" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Service</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="receivables-type" wire:model="draftTransactionType">
                            <option value="all">All services</option>
                            @foreach ($typeLabels as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label for="receivables-organization" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Organisation</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="receivables-organization" wire:model="draftOrganizationId">
                            <option value="">All organisations</option>
                            @foreach ($this->organizationOptions() as $organizationId => $organizationName)
                                <option value="{{ $organizationId }}">{{ $organizationName }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label for="receivables-per-page" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Rows per page</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="receivables-per-page" wire:model.live="perPage">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label for="receivables-from" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Created from</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="receivables-from" wire:model="draftFromDate" type="date" />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label for="receivables-until" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Created until</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="receivables-until" wire:model="draftUntilDate" type="date" />
                    </x-filament::input.wrapper>
                </div>

                <div class="flex items-end gap-2 md:col-span-2 xl:col-span-6">
                    <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="applyFilters">Apply filters</x-filament::button>
                    <x-filament::button type="button" color="gray" wire:click="clearFilters" wire:loading.attr="disabled" wire:target="clearFilters">Clear</x-filament::button>
                </div>
            </form>
            @error('draftFromDate')<p class="mt-3 text-sm text-danger-600">{{ $message }}</p>@enderror
            @error('draftUntilDate')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            <p wire:loading.delay wire:target="applyFilters,clearFilters" role="status" aria-live="polite" class="mt-3 text-sm font-medium text-primary-600 dark:text-primary-400">Updating receivables&hellip;</p>
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

        <x-filament::section heading="Transactions awaiting payment" description="Record the remaining balance received through cash, mobile money, card, or bank transfer." x-data="corporatePaymentReview()" x-on:keydown.escape.window="closeReview()">
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
                        <caption class="sr-only">Outstanding corporate transactions awaiting settlement</caption>
                        <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-3 py-3 font-semibold">Transaction</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Organisation</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Guest</th>
                                <th scope="col" class="px-3 py-3 text-right font-semibold">Outstanding</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Created</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Clear payment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($receivables as $item)
                                <tr class="align-top hover:bg-gray-50 dark:hover:bg-white/5">
                                    <th scope="row" class="px-3 py-4 font-semibold text-gray-950 dark:text-white">{{ $item['label'] }} #{{ $item['id'] }}<span class="mt-1 block text-xs font-normal text-gray-500">{{ ucfirst($item['status']) }}</span></th>
                                    <td class="px-3 py-4 text-gray-700 dark:text-gray-300">{{ $item['organization'] }}</td>
                                    <td class="px-3 py-4 text-gray-700 dark:text-gray-300">{{ $item['guest'] }}</td>
                                    <td class="px-3 py-4 text-right font-bold text-danger-600 dark:text-danger-400">GHS {{ number_format($item['amount'], 2) }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-gray-600 dark:text-gray-400">{{ $item['created_at']?->format('M d, Y') }}</td>
                                    <td class="px-3 py-4">
                                        <form id="payment-form-{{ $item['type'] }}-{{ $item['id'] }}" method="POST" action="{{ route('admin.corporate-receivables.pay', [$item['type'], $item['id']]) }}" class="flex min-w-[24rem] flex-wrap items-center gap-2" data-label="{{ $item['label'] }} #{{ $item['id'] }}" data-amount="GHS {{ number_format($item['amount'], 2) }}" data-organization="{{ $item['organization'] }}" data-guest="{{ $item['guest'] }}" data-created="{{ $item['created_at']?->format('M d, Y') }}" x-on:submit.prevent="openReview($el)">
                                            @csrf
                                            <label class="sr-only" for="method-{{ $item['type'] }}-{{ $item['id'] }}">Payment method</label>
                                            <select id="method-{{ $item['type'] }}-{{ $item['id'] }}" name="method" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                                                <option value="cash">Cash</option><option value="momo">Mobile money</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option>
                                            </select>
                                            <label class="sr-only" for="reference-{{ $item['type'] }}-{{ $item['id'] }}">Transaction reference</label>
                                            <input id="reference-{{ $item['type'] }}-{{ $item['id'] }}" name="transaction_reference" class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900" placeholder="Reference (optional)">
                                            <button type="button" x-on:click="openReview($el.form)" class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500">Review payment</button>
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
                            <form id="mobile-payment-form-{{ $item['type'] }}-{{ $item['id'] }}" method="POST" action="{{ route('admin.corporate-receivables.pay', [$item['type'], $item['id']]) }}" class="mt-4 space-y-2" data-label="{{ $item['label'] }} #{{ $item['id'] }}" data-amount="GHS {{ number_format($item['amount'], 2) }}" data-organization="{{ $item['organization'] }}" data-guest="{{ $item['guest'] }}" data-created="{{ $item['created_at']?->format('M d, Y') }}" x-on:submit.prevent="openReview($el)">
                                @csrf
                                <label class="sr-only" for="mobile-method-{{ $item['type'] }}-{{ $item['id'] }}">Payment method</label>
                                <select id="mobile-method-{{ $item['type'] }}-{{ $item['id'] }}" name="method" class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                                    <option value="cash">Cash</option><option value="momo">Mobile money</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option>
                                </select>
                                <label class="sr-only" for="mobile-reference-{{ $item['type'] }}-{{ $item['id'] }}">Transaction reference</label>
                                <input id="mobile-reference-{{ $item['type'] }}-{{ $item['id'] }}" name="transaction_reference" class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900" placeholder="Reference (optional)">
                                <button type="button" x-on:click="openReview($el.form)" class="w-full rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500">Review payment</button>
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

            <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="corporate-payment-review-title">
                <div class="flex min-h-full items-center justify-center bg-gray-950/60 p-4" x-on:click.self="closeReview()">
                    <div x-show="open" x-transition class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10" x-on:click.stop>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Payment review</p>
                                <h2 id="corporate-payment-review-title" class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Confirm settlement</h2>
                            </div>
                            <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-gray-200" x-on:click="closeReview()" aria-label="Close payment review">&times;</button>
                        </div>

                        <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-300">Review the details below before the outstanding balance is marked as paid. This action records an offline payment and updates the guest dashboard.</p>

                        <dl class="mt-5 divide-y divide-gray-200 rounded-xl border border-gray-200 text-sm dark:divide-white/10 dark:border-white/10">
                            <div class="flex items-start justify-between gap-4 p-4"><dt class="text-gray-500 dark:text-gray-400">Transaction</dt><dd class="text-right font-semibold text-gray-950 dark:text-white" x-text="transaction.label"></dd></div>
                            <div class="flex items-start justify-between gap-4 p-4"><dt class="text-gray-500 dark:text-gray-400">Organisation</dt><dd class="text-right font-medium text-gray-950 dark:text-white" x-text="transaction.organization"></dd></div>
                            <div class="flex items-start justify-between gap-4 p-4"><dt class="text-gray-500 dark:text-gray-400">Guest</dt><dd class="text-right font-medium text-gray-950 dark:text-white" x-text="transaction.guest"></dd></div>
                            <div class="flex items-start justify-between gap-4 p-4"><dt class="text-gray-500 dark:text-gray-400">Outstanding amount</dt><dd class="text-right font-bold text-danger-600 dark:text-danger-400" x-text="transaction.amount"></dd></div>
                            <div class="flex items-start justify-between gap-4 p-4"><dt class="text-gray-500 dark:text-gray-400">Payment method</dt><dd class="text-right font-medium text-gray-950 dark:text-white" x-text="transaction.method"></dd></div>
                            <div class="flex items-start justify-between gap-4 p-4"><dt class="text-gray-500 dark:text-gray-400">Reference</dt><dd class="max-w-[14rem] break-words text-right font-medium text-gray-950 dark:text-white" x-text="transaction.reference"></dd></div>
                            <div class="flex items-start justify-between gap-4 p-4"><dt class="text-gray-500 dark:text-gray-400">Recorded by</dt><dd class="text-right font-medium text-gray-950 dark:text-white">{{ auth()->user()?->name ?? 'Current staff member' }}</dd></div>
                        </dl>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <x-filament::button type="button" color="gray" x-on:click="closeReview()">Go back</x-filament::button>
                            <x-filament::button type="button" x-ref="confirmButton" x-on:click="confirmPayment()">Confirm and mark paid</x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>

    <script>
        function corporatePaymentReview() {
            return {
                open: false,
                formId: null,
                transaction: {},

                openReview(form) {
                    if (! form) {
                        return;
                    }

                    const method = form.querySelector('[name="method"]');
                    const reference = form.querySelector('[name="transaction_reference"]');

                    this.formId = form.id;
                    this.transaction = {
                        label: form.dataset.label,
                        amount: form.dataset.amount,
                        organization: form.dataset.organization,
                        guest: form.dataset.guest,
                        method: method?.options[method.selectedIndex]?.text ?? 'Not selected',
                        reference: reference?.value.trim() || 'Auto-generated when recorded',
                    };
                    this.open = true;
                    this.$nextTick(() => this.$refs.confirmButton?.focus());
                },

                closeReview() {
                    this.open = false;
                },

                confirmPayment() {
                    const form = this.formId ? document.getElementById(this.formId) : null;

                    if (! form) {
                        this.closeReview();

                        return;
                    }

                    this.open = false;
                    form.submit();
                },
            };
        }
    </script>
</x-filament::page>
