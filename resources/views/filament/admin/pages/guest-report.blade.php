<x-filament::page>
    @php($report = $this->report())

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::section heading="Total guests"><div class="text-2xl font-bold">{{ number_format($report['total']) }}</div></x-filament::section>
        <x-filament::section heading="New this month"><div class="text-2xl font-bold">{{ number_format($report['newThisMonth']) }}</div></x-filament::section>
        <x-filament::section heading="Returning guests"><div class="text-2xl font-bold">{{ number_format($report['returning']) }}</div></x-filament::section>
        <x-filament::section heading="Average payment"><div class="text-2xl font-bold">GHS {{ number_format($report['averageSpend'], 2) }}</div></x-filament::section>
    </div>

    <x-filament::section heading="Most frequent hotel guests">
        <div class="space-y-2">
            @forelse ($report['topGuests'] as $guest)
                <div class="flex justify-between"><span>{{ $guest->full_name }} <span class="text-gray-500">{{ $guest->email }}</span></span><strong>{{ $guest->bookings_count }} bookings</strong></div>
            @empty
                <p>No guest activity recorded.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament::page>
