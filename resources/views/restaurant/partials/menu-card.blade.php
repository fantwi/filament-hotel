<article class="overflow-hidden rounded-xl bg-white shadow" x-show="matches(@js($item->name.' '.$item->description))" x-transition>
    @if ($item->image)
        <img src="{{ asset('storage/'.$item->image) }}" class="h-56 w-full object-cover" alt="{{ $item->name }}">
    @endif

    <div class="p-6">
        <h3 class="text-xl font-bold">{{ $item->name }}</h3>
        <p class="mt-3 text-gray-600">{{ $item->description }}</p>

        <div class="mt-4 flex items-center justify-between">
            <span class="text-xl font-bold">GHS {{ number_format($item->price, 2) }}</span>
            <span class="text-gray-500">{{ $item->preparation_time }} mins</span>
        </div>

        <form action="{{ route('cart.add', $item) }}" method="POST">
            @csrf
            <button class="mt-6 w-full rounded-lg bg-blue-600 py-3 text-white transition hover:bg-blue-700">
                Add to Cart
            </button>
        </form>
    </div>
</article>
