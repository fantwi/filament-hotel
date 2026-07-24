<x-guest-layout>

@if(session('success'))

    <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700">

        {{ session('success') }}

    </div>

@endif

<section class="relative">

    <img
        src="{{ asset('storage/' . $restaurant->hero_image) }}"
        class="h-80 w-full object-cover sm:h-[500px]"
        alt="{{ $restaurant->name }}"
    >

    <div
        class="absolute inset-0
        bg-black/50
        flex flex-col
        justify-center
        items-center
        text-white"
    >

        <h1 class="mb-4 px-4 text-center text-3xl font-bold sm:text-5xl">
            {{ $restaurant->name }}
        </h1>

        <p class="px-4 text-center text-base sm:text-xl">
            {{ $restaurant->description }} • Local & International Cuisine
        </p>

        <p>
        <a
            href="{{ route('restaurant.reserve') }}"
            class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
        >
            Reserve a Table
        </a>
        </p>

    </div>

    <!-- <a
        href="{{ route('restaurant.reserve') }}"
        class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
    >
        Reserve a Table
    </a> -->

</section>

<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-16 lg:px-8">

    <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Our Story</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">About Our Restaurant</h2>
            <p class="mt-6 max-w-xl text-base leading-8 text-gray-600 sm:text-lg">
                {{ $restaurant->description ?: 'Experience world-class dining prepared by our professional chefs, with local Ghanaian favourites, continental cuisine, desserts, cocktails, and beverages.' }}
            </p>
            <a href="{{ route('restaurant.reserve') }}" class="mt-8 inline-flex rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white transition hover:bg-indigo-700">Reserve a Table</a>
        </div>

        <div class="rounded-2xl bg-gradient-to-br from-indigo-950 to-slate-900 p-5 text-white shadow-xl sm:p-8">
            <div class="flex items-center justify-between border-b border-white/15 pb-5">
                <div>
                    <p class="text-sm font-medium text-indigo-200">Plan your visit</p>
                    <h3 class="mt-1 text-2xl font-bold">Restaurant Details</h3>
                </div>
                <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-sm font-semibold text-emerald-200">{{ $restaurant->is_open ? 'Open' : 'Closed' }}</span>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-white/10 p-4"><dt class="text-sm text-indigo-200">Opening time</dt><dd class="mt-1 text-lg font-semibold">{{ $restaurant->opening_time ? \Carbon\Carbon::parse($restaurant->opening_time)->format('g:i A') : 'Contact us' }}</dd></div>
                <div class="rounded-xl bg-white/10 p-4"><dt class="text-sm text-indigo-200">Closing time</dt><dd class="mt-1 text-lg font-semibold">{{ $restaurant->closing_time ? \Carbon\Carbon::parse($restaurant->closing_time)->format('g:i A') : 'Contact us' }}</dd></div>
                <div class="rounded-xl bg-white/10 p-4"><dt class="text-sm text-indigo-200">Dining capacity</dt><dd class="mt-1 text-lg font-semibold">{{ $restaurant->capacity ?: '—' }} guests</dd></div>
                <div class="rounded-xl bg-white/10 p-4"><dt class="text-sm text-indigo-200">Cuisine</dt><dd class="mt-1 text-lg font-semibold">{{ $restaurant->cuisine ?: 'Chef’s selection' }}</dd></div>
                <div class="rounded-xl bg-white/10 p-4 sm:col-span-2"><dt class="text-sm text-indigo-200">Dress code</dt><dd class="mt-1 text-lg font-semibold">{{ $restaurant->dress_code ?: 'Smart casual' }}</dd></div>
            </dl>
        </div>
    </div>

</section>

<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-16 lg:px-8">
    <h2 class="text-4xl font-bold mb-10">Featured Meals</h2>

    <div class="grid gap-8 md:grid-cols-4">
        @forelse ($featuredItems as $item)
            <div class="overflow-hidden rounded-xl bg-white shadow">
                <img
                    src="{{ $item->image ? asset('storage/'.$item->image) : asset('images/meal-placeholder.svg') }}"
                    class="h-56 w-full object-cover"
                    alt="{{ $item->name }}"
                    loading="lazy"
                >
                <div class="p-6">
                    <h3 class="text-xl font-bold">{{ $item->name }}</h3>
                    <p class="mt-2 text-gray-600">{{ $item->description }}</p>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-lg font-bold">GHS {{ number_format($item->price, 2) }}</span>
                        <span class="text-sm text-gray-500">{{ $item->preparation_time }} mins</span>
                    </div>
                    <form action="{{ route('cart.add', $item) }}" method="POST" class="mt-5">
                        @csrf
                        <button type="submit" class="w-full rounded-lg bg-blue-600 py-3 font-semibold text-white transition hover:bg-blue-700">
                            Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-gray-500">Featured meals will be available soon.</p>
        @endforelse
    </div>

    <div class="mt-10 text-center">
        <a href="{{ route('restaurant.menu') }}" class="inline-flex items-center rounded-lg bg-green-600 px-6 py-3 font-semibold text-white transition hover:bg-green-700">
            View Full Menu
        </a>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-16 lg:px-8">

    <h2 class="text-3xl font-bold mb-8">
        Our Restaurant Tables
    </h2>

    <div class="grid md:grid-cols-3 gap-6">

        @foreach($restaurant->tables as $table)

            <div class="overflow-hidden rounded-xl bg-white shadow">
                <img
                    src="{{ $table->image ? asset('storage/'.$table->image) : asset('images/table-placeholder.svg') }}"
                    class="h-48 w-full object-cover"
                    alt="{{ $table->table_number }}"
                    loading="lazy"
                >

                <div class="p-6">

                    <h3 class="text-xl font-bold">
                        {{ $table->table_number }}
                    </h3>

                    <p>
                        Capacity: {{ $table->capacity }}
                    </p>

                    <p>
                        Location: {{ $table->location }}
                    </p>

                    <span class="inline-block mt-3 px-3 py-1 rounded-full bg-green-100 text-green-700">
                        {{ ucfirst($table->status) }}
                    </span>

                    @if ($table->status === 'available')
                        <a href="{{ route('restaurant.reserve', ['table' => $table->id]) }}" class="mt-5 block rounded-lg bg-indigo-600 px-4 py-2.5 text-center font-semibold text-white transition hover:bg-indigo-700">
                            Reserve This Table
                        </a>
                    @else
                        <p class="mt-5 text-sm font-medium text-gray-500">This table is currently unavailable.</p>
                    @endif
                </div>

            </div>

        @endforeach

    </div>

    <div class="mt-10 text-center">
        <a href="{{ route('restaurant.tables') }}" class="inline-flex items-center rounded-lg border border-indigo-600 px-6 py-3 font-semibold text-indigo-700 transition hover:bg-indigo-50">
            View All Restaurant Tables
        </a>
    </div>

</section>

{{-- Static facilities retained only as a reference; management now happens in Filament. --}}
{{--
<section class="py-16">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <h2
            class="text-3xl
            font-bold
            text-center
            mb-10"
        >

            Facilities

        </h2>

        <div
            class="grid
            md:grid-cols-3
            gap-6
            text-center"
        >

            <div>🍷 Bar</div>

            <div>📶 Free WiFi</div>

            <div>🎵 Live Music</div>

            <div>❄ Air Conditioned</div>

            <div>👶 Family Friendly</div>

            <div>🚗 Parking</div>

        </div>

    </div>

</section>
--}}

@if ($restaurant?->facilities->isNotEmpty())
    <section class="bg-gray-50 py-8 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Amenities</p>
                <h2 class="mt-2 text-3xl font-bold text-gray-900 sm:text-4xl">Restaurant Facilities</h2>
            </div>

            <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($restaurant->facilities as $facility)
                    <div class="rounded-xl bg-white p-5 text-center shadow-sm ring-1 ring-gray-950/5">
                        <div class="text-2xl" aria-hidden="true">{{ $facility->icon ?: '✓' }}</div>
                        <p class="mt-3 font-semibold text-gray-900">{{ $facility->name }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($restaurant && filled($restaurant->gallery) && is_array($restaurant->gallery))
    <section
        class="bg-gray-50 py-16"
        x-data="{
            selectedImage: null,
            open(image) { this.selectedImage = image; document.body.classList.add('overflow-hidden') },
            close() { this.selectedImage = null; document.body.classList.remove('overflow-hidden') }
        }"
        @keydown.escape.window="close()"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-widest text-orange-600">Gallery</p>
                <h2 class="mt-2 text-3xl font-bold text-gray-900 md:text-4xl">Experience Our Restaurant</h2>
                <p class="mt-4 text-gray-600">Explore our dining space, atmosphere, meals, and memorable guest experiences.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (array_slice($restaurant->gallery, 0, 3) as $image)
                    @php($imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($image))
                    <button
                        type="button"
                        class="group relative aspect-[4/3] overflow-hidden rounded-2xl bg-gray-200 shadow-sm focus:outline-none focus:ring-4 focus:ring-orange-200"
                        @click="open(@js($imageUrl))"
                    >
                        <img src="{{ $imageUrl }}" alt="{{ $restaurant->name }} gallery image {{ $loop->iteration }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 transition group-hover:opacity-100"></div>
                        <div class="absolute bottom-4 right-4 rounded-full bg-white/90 px-4 py-2 text-sm font-medium text-gray-800 opacity-0 transition group-hover:opacity-100">View Image</div>
                    </button>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <a href="{{ route('restaurant.gallery') }}" class="inline-flex items-center rounded-lg border border-orange-600 px-6 py-3 font-semibold text-orange-700 transition hover:bg-orange-50">
                    View More Gallery Images
                </a>
            </div>
        </div>

        <div x-show="selectedImage" x-cloak x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4 sm:p-8" role="dialog" aria-modal="true" @click.self="close()">
            <button type="button" class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-3xl text-white transition hover:bg-white/20" @click="close()" aria-label="Close gallery image">&times;</button>
            <img :src="selectedImage" alt="Restaurant gallery preview" class="max-h-[88vh] max-w-full rounded-xl object-contain shadow-2xl">
        </div>
    </section>
@endif

<section class="py-20">

    <div class="text-center">

        <h2 class="text-4xl font-bold">

            Ready for a Great Dining Experience?

        </h2>

        <div class="mt-10 flex flex-col items-center justify-center gap-3 px-4 sm:flex-row sm:gap-5">

            <!-- <a
                href="/restaurant/reservations"
                class="bg-blue-600
                text-white
                px-8 py-4
                rounded-lg"
            >

                Reserve a Table

            </a> -->

            <a
                href="{{ route('restaurant.reserve') }}"
                class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
            >
                Reserve a Table
            </a>

            <a
                href="{{ route('restaurant.menu') }}"
                class="bg-green-600
                text-white
                px-8 py-4
                rounded-lg"
            >

                View Full Menu

            </a>

        </div>

    </div>

</section>

</x-guest-layout>
