<x-filament::page>
    @php($report = $this->report())

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach (['daily' => 'Today', 'weekly' => 'This week', 'monthly' => 'This month', 'annual' => 'This year', 'refunds' => 'Refunds', 'outstanding' => 'Outstanding'] as $key => $label)
            <x-filament::section>
                <x-slot name="heading">{{ $label }}</x-slot>
                <div class="text-2xl font-bold">GHS {{ number_format($report[$key], 2) }}</div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section heading="Payment methods">
        <div class="grid gap-3 md:grid-cols-3">
            @forelse ($report['methods'] as $method => $total)
                <div><strong>{{ ucfirst($method ?: 'Unknown') }}</strong><br>GHS {{ number_format($total, 2) }}</div>
            @empty
                <p>No completed payments recorded.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament::page>
