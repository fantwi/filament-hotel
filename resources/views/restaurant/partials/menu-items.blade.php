<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
    @foreach ($categories as $category)
        @if ($category->menuItems->isNotEmpty())
            <section id="category{{ $category->id }}" class="mb-20 scroll-mt-24">
                <h2 class="mb-6 text-2xl font-bold sm:mb-8 sm:text-3xl">{{ $category->name }}</h2>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($category->menuItems as $item)
                        @include('restaurant.partials.menu-card', ['item' => $item])
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</div>
