<x-guest-layout>
    @if (session()->has('restaurant_order.table_id'))
        <div class="border-b border-green-200 bg-green-50">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold text-green-800">Ordering for Table {{ session('restaurant_order.table_number') }}</p>
                    <p class="text-sm text-green-700">Items added to your cart will be delivered to this table.</p>
                </div>
                <form method="POST" action="{{ route('restaurant.table.leave') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700" onclick="return confirm('Clear this table session and cart?')">Leave Table</button>
                </form>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="mx-auto mt-6 max-w-7xl rounded-lg bg-green-100 px-5 py-4 text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mx-auto mt-6 max-w-7xl rounded-lg bg-red-100 px-5 py-4 text-red-700">{{ session('error') }}</div>
    @endif

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
