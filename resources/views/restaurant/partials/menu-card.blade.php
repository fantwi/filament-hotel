<article class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5" x-show="matches(@js($item->name.' '.$item->description))" x-transition>
    <img
        src="{{ $item->image ? asset('storage/'.$item->image) : asset('images/meal-placeholder.svg') }}"
        class="h-56 w-full object-cover"
        alt="{{ $item->name }}"
        loading="lazy"
    >

    <div class="p-6">
        <h3 class="text-xl font-bold">{{ $item->name }}</h3>
        <p class="mt-3 text-gray-600">{{ $item->description }}</p>

        <div class="mt-4 flex items-center justify-between">
            <span class="text-xl font-bold">GHS {{ number_format($item->price, 2) }}</span>
            <span class="text-gray-500">{{ $item->preparation_time }} mins</span>
        </div>

        <form action="{{ route('cart.add', $item) }}" method="POST">
            @csrf
            <button class="mt-6 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 py-3 font-semibold text-white transition hover:bg-blue-700">
                Add to cart
            </button>
        </form>
    </div>
</article>
