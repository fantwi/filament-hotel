<x-guest-layout>
    <section class="bg-gray-900 py-14 text-white sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-widest text-amber-300">Restaurant</p>
            <h1 class="mt-2 text-3xl font-bold sm:text-5xl">Our Restaurant Tables</h1>
            <p class="mt-4 max-w-2xl text-gray-200">Explore our available dining spaces and find the setting that suits your visit.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-16 lg:px-8">
        @if ($restaurant?->tables->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($restaurant->tables as $table)
                    <article class="overflow-hidden rounded-xl bg-white shadow">
                        <img
                            src="{{ $table->image ? asset('storage/'.$table->image) : asset('images/table-placeholder.svg') }}"
                            class="h-52 w-full object-cover"
                            alt="{{ $table->table_number }}"
                            loading="lazy"
                        >
                        <div class="p-6">
                            <h2 class="text-xl font-bold">{{ $table->table_number }}</h2>
                            <dl class="mt-4 space-y-2 text-sm text-gray-600">
                                <div class="flex justify-between gap-4"><dt>Capacity</dt><dd class="font-medium text-gray-900">{{ $table->capacity }} guests</dd></div>
                                <div class="flex justify-between gap-4"><dt>Location</dt><dd class="font-medium text-gray-900">{{ $table->location ?: 'Restaurant' }}</dd></div>
                            </dl>
                            <span class="mt-5 inline-block rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700">{{ ucfirst($table->status) }}</span>
                            @if ($table->status === 'available')
                                <a href="{{ route('restaurant.reserve', ['table' => $table->id]) }}" class="mt-5 block rounded-lg bg-indigo-600 px-4 py-2.5 text-center font-semibold text-white transition hover:bg-indigo-700">Reserve This Table</a>
                            @else
                                <p class="mt-5 text-sm font-medium text-gray-500">This table is currently unavailable.</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-xl bg-white p-8 text-center shadow">
                <h2 class="text-xl font-bold">Restaurant tables will be available soon.</h2>
                <a href="{{ route('restaurant') }}" class="mt-5 inline-flex rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white">Back to Restaurant</a>
            </div>
        @endif
    </section>
</x-guest-layout>
