<x-guest-layout>
    <div class="mx-auto max-w-5xl px-6 py-12">
        <h1 class="mb-8 text-4xl font-bold">Your Cart</h1>

        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg bg-red-100 p-4 text-red-700">{{ session('error') }}</div>
        @endif

        @if ($cartItems->isEmpty())
            <p class="rounded-xl bg-white p-6 shadow">Your cart is empty. <a href="{{ route('restaurant.menu') }}" class="text-blue-600">Browse the menu</a>.</p>
        @else
            <div class="space-y-4">
                @foreach ($cartItems as $line)
                    @php($item = $line['item'])
                    <div class="flex flex-col gap-4 rounded-xl bg-white p-5 shadow sm:flex-row sm:items-center">
                        @if ($item->image)
                            <img src="{{ asset('storage/'.$item->image) }}" class="h-20 w-20 rounded-lg object-cover" alt="{{ $item->name }}">
                        @endif
                        <div class="flex-1"><h2 class="text-lg font-bold">{{ $item->name }}</h2><p>GHS {{ number_format($item->price, 2) }} each</p></div>
                        <form action="{{ route('cart.update', $item) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            <label class="sr-only" for="quantity-{{ $item->id }}">Quantity</label>
                            <input id="quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="99" value="{{ $line['quantity'] }}" class="w-20 rounded border-gray-300">
                            <button class="rounded bg-gray-100 px-3 py-2">Update</button>
                        </form>
                        <strong>GHS {{ number_format($line['line_total'], 2) }}</strong>
                        <form action="{{ route('cart.remove', $item) }}" method="POST">@csrf @method('DELETE')<button class="text-red-600">Remove</button></form>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 ml-auto max-w-md rounded-xl bg-white p-6 shadow">
                <div class="flex justify-between"><span>Subtotal</span><span>GHS {{ number_format($totals['subtotal'], 2) }}</span></div>
                <div class="mt-2 flex justify-between"><span>Tax</span><span>GHS {{ number_format($totals['tax'], 2) }}</span></div>
                <div class="mt-2 flex justify-between"><span>Service charge</span><span>GHS {{ number_format($totals['service_charge'], 2) }}</span></div>
                <div class="mt-4 flex justify-between border-t pt-4 text-xl font-bold"><span>Total</span><span>GHS {{ number_format($totals['total'], 2) }}</span></div>
                <a href="{{ route('restaurant.checkout') }}" class="mt-6 block rounded-lg bg-blue-600 py-3 text-center text-white">Checkout</a>
            </div>
        @endif
    </div>
</x-guest-layout>
