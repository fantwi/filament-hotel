<x-filament::page>
    @php($report = $this->report())

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            'occupiedRooms' => 'Rooms occupied',
            'availableRooms' => 'Rooms available',
            'maintenanceRooms' => 'Rooms under maintenance',
            'availableConferenceRooms' => 'Conference rooms available',
            'unavailableConferenceRooms' => 'Conference rooms unavailable',
            'reservedTables' => 'Restaurant tables reserved',
            'occupiedTables' => 'Restaurant tables occupied',
            'availableTables' => 'Restaurant tables available',
        ] as $key => $label)
            <x-filament::section heading="{{ $label }}">
                <div class="text-2xl font-bold">{{ number_format($report[$key]) }}</div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament::page>
