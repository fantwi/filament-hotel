<section class="bg-white py-12">
    <div class="mx-auto max-w-7xl px-6">
        <h2 class="mb-8 text-4xl font-bold">Featured Meals</h2>

        <div class="grid gap-8 md:grid-cols-4">
            @forelse ($featuredItems as $item)
                @include('restaurant.partials.menu-card', ['item' => $item])
            @empty
                <p class="text-gray-500">Featured meals will be available soon.</p>
            @endforelse
        </div>
    </div>
</section>
