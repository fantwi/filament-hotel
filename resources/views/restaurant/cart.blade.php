<x-guest-layout>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
        <div class="mb-7">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Restaurant order</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">Your cart</h1>
        </div>

        @if (session()->has('restaurant_order.table_id'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-5">
                <p class="font-semibold text-green-800">Dine-in Order</p>
                <p class="text-green-700">Table: {{ session('restaurant_order.table_number') }}</p>
            </div>
        @else
            <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-5">
                <p class="font-semibold text-blue-800">Standard Restaurant Order</p>
                <p class="text-blue-700">No restaurant table is attached to this cart.</p>
            </div>
        @endif

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
                    <div class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:flex-row sm:items-center">
                        @if ($item->image)
                            <img src="{{ asset('storage/'.$item->image) }}" class="h-20 w-20 rounded-lg object-cover" alt="{{ $item->name }}">
                        @endif
                        <div class="flex-1"><h2 class="text-lg font-bold">{{ $item->name }}</h2><p>GHS {{ number_format($item->price, 2) }} each</p></div>
                        <form action="{{ route('cart.update', $item) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            <label class="sr-only" for="quantity-{{ $item->id }}">Quantity</label>
                            <input id="quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="99" value="{{ $line['quantity'] }}" class="min-h-11 w-20 rounded-xl border-gray-300 px-3">
                            <button class="min-h-11 rounded-xl bg-gray-100 px-3 font-semibold">Update</button>
                        </form>
                        <strong>GHS {{ number_format($line['line_total'], 2) }}</strong>
                        <form action="{{ route('cart.remove', $item) }}" method="POST">@csrf @method('DELETE')<button class="text-red-600">Remove</button></form>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 ml-auto max-w-md rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5">
                <form action="{{ route('cart.index') }}" method="GET" class="mb-5 border-b border-slate-200 pb-5">
                    <label for="promotion_code" class="block text-sm font-semibold text-gray-800">Discount code</label>
                    <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                        <input id="promotion_code" name="promotion_code" type="text" value="{{ $promotionCode ?? '' }}" class="min-h-11 flex-1 rounded-xl border-gray-300 px-3 text-base" placeholder="Enter a discount code">
                        <button type="submit" class="min-h-11 rounded-xl border border-blue-600 px-4 font-semibold text-blue-700">Apply discount</button>
                    </div>
                    @if ($promotionError)
                        <p class="mt-2 text-sm text-red-600">{{ $promotionError }}</p>
                    @endif
                </form>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>GHS {{ number_format($totals['subtotal'], 2) }}</span></div>
                    <div class="flex justify-between text-green-700"><span>Discount{{ $promotionCode ? ' ('.$promotionCode.')' : '' }}</span><span>- GHS {{ number_format($totals['discount'], 2) }}</span></div>
                    <div class="flex justify-between font-medium"><span>Net after discount</span><span>GHS {{ number_format($totals['net'], 2) }}</span></div>
                    <div class="flex justify-between"><span>Service charge ({{ number_format($totals['service_charge_rate'], 2) }}%)</span><span>GHS {{ number_format($totals['service_charge'], 2) }}</span></div>
                    <div class="flex justify-between"><span>VAT ({{ number_format($totals['vat_rate'], 2) }}%)</span><span>GHS {{ number_format($totals['vat'], 2) }}</span></div>
                    <div class="flex justify-between"><span>NHIL ({{ number_format($totals['nhil_rate'], 2) }}%)</span><span>GHS {{ number_format($totals['nhil'], 2) }}</span></div>
                </div>

                <div class="mt-4 flex justify-between border-t border-slate-200 pt-4 text-xl font-bold"><span>Estimated total</span><span>GHS {{ number_format($totals['total'], 2) }}</span></div>
                <a href="{{ route('restaurant.checkout', ['promotion_code' => $promotionCode]) }}" class="mt-6 flex min-h-12 items-center justify-center rounded-xl bg-blue-600 py-3 text-center font-semibold text-white">Checkout</a>
            </div>
        @endif
    </div>
</x-guest-layout>
