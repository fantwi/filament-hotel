<section class="sticky top-0 z-20 border-y bg-white shadow-sm">
    <div class="mx-auto max-w-7xl overflow-x-auto px-6 py-4">
        <div class="flex w-max gap-4">
            @foreach ($categories as $category)
                <a href="#category{{ $category->id }}" class="whitespace-nowrap rounded-full bg-blue-100 px-5 py-2 transition hover:bg-blue-600 hover:text-white">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </div>
</section>
