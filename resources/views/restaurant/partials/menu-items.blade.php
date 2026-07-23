<div class="mx-auto max-w-7xl px-6 py-10">
    @foreach ($categories as $category)
        @if ($category->menuItems->isNotEmpty())
            <section id="category{{ $category->id }}" class="mb-20 scroll-mt-24">
                <h2 class="mb-8 text-3xl font-bold">{{ $category->name }}</h2>

                <div class="grid gap-8 md:grid-cols-3">
                    @foreach ($category->menuItems as $item)
                        @include('restaurant.partials.menu-card', ['item' => $item])
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</div>
