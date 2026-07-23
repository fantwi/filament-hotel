<x-guest-layout>
    <div class="min-h-screen bg-gray-50" x-data="{ query: '', matches(value) { return value.toLowerCase().includes(this.query.toLowerCase()) } }">
        <section class="relative overflow-hidden bg-gray-900 py-20 text-white">
            @if ($restaurant?->hero_image)
                <img src="{{ asset('storage/'.$restaurant->hero_image) }}" class="absolute inset-0 h-full w-full object-cover opacity-30" alt="">
            @endif
            <div class="relative mx-auto max-w-7xl px-6">
                <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-amber-300">Restaurant</p>
                <h1 class="text-4xl font-bold md:text-5xl">{{ $restaurant?->name ?? 'Our Menu' }}</h1>
                <p class="mt-4 max-w-2xl text-lg text-gray-200">Browse our freshly prepared meals, drinks, and desserts.</p>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-6 py-10">
            <label for="menu-search" class="sr-only">Search menu</label>
            <input id="menu-search" x-model="query" type="search" placeholder="Search menu..." class="w-full rounded-xl border border-gray-300 px-6 py-4 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        @include('restaurant.partials.featured-items')
        @include('restaurant.partials.category-nav')
        @include('restaurant.partials.menu-items')
    </div>
</x-guest-layout>
