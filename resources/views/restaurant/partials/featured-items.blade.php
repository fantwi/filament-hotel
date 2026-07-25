<section class="bg-white py-10 sm:py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <h2 class="mb-6 text-2xl font-bold sm:mb-8 sm:text-3xl">Featured meals</h2>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($featuredItems as $item)
                @include('restaurant.partials.menu-card', ['item' => $item])
            @empty
                <p class="text-gray-500">Featured meals will be available soon.</p>
            @endforelse
        </div>
    </div>
</section>
