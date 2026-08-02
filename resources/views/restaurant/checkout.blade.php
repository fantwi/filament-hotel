<x-guest-layout>
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
        <div class="mb-7"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Restaurant order</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">Checkout</h1></div>
        <form action="{{ route('restaurant.checkout') }}" method="GET" class="mb-5 rounded-2xl border border-indigo-100 bg-indigo-50 p-5 sm:flex sm:items-end sm:gap-4">
            <div class="flex-1"><label for="apply_promotion_code" class="block text-sm font-semibold text-gray-800">Discount code</label><input id="apply_promotion_code" name="promotion_code" type="text" value="{{ $promotionCode ?? '' }}" class="mt-2 min-h-12 w-full rounded-xl border-gray-300 px-4 text-base" placeholder="Enter a discount code">@if ($promotionError)<p class="mt-2 text-sm text-red-600">{{ $promotionError }}</p>@endif</div>
            <button type="submit" class="mt-3 flex min-h-12 w-full items-center justify-center rounded-xl border border-indigo-600 bg-white px-5 font-semibold text-indigo-700 sm:mt-0 sm:w-auto">Apply discount</button>
        </form>
        <form action="{{ route('restaurant.checkout.store') }}" method="POST" class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
            @csrf
            <input type="hidden" name="promotion_code" value="{{ $promotionCode ?? '' }}">
            <h2 class="mb-4 text-xl font-bold">Order summary</h2>
            <div class="space-y-2">@foreach ($cartItems as $line)<div class="flex justify-between gap-4"><span>{{ $line['quantity'] }} x {{ $line['item']->name }}</span><span class="shrink-0">GHS {{ number_format($line['line_total'], 2) }}</span></div>@endforeach</div>
            <div class="mt-5 space-y-2 border-t pt-4 text-sm">
                <div class="flex justify-between"><span>Subtotal</span><span>GHS {{ number_format($totals['subtotal'], 2) }}</span></div>
                <div class="flex justify-between text-green-700"><span>Discount</span><span>- GHS {{ number_format($totals['discount'] ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span>Service charge</span><span>GHS {{ number_format($totals['service_charge'], 2) }}</span></div>
                <div class="flex justify-between"><span>VAT</span><span>GHS {{ number_format($totals['vat'] ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span>NHIL</span><span>GHS {{ number_format($totals['nhil'] ?? 0, 2) }}</span></div>
            </div>
            <div class="mt-4 flex justify-between border-t pt-4 text-xl font-bold"><span>Estimated total</span><span>GHS {{ number_format($totals['total'], 2) }}</span></div>
            <label for="email" class="mt-6 block font-medium">Email for payment receipt</label><input id="email" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" required class="mt-2 min-h-12 w-full rounded-xl border-gray-300 px-4 text-base">
            <label for="notes" class="mt-6 block font-medium">Order notes</label><textarea id="notes" name="notes" rows="4" class="mt-2 w-full rounded-xl border-gray-300 px-4 py-3 text-base" placeholder="Allergies or special instructions">{{ old('notes') }}</textarea>
            <button class="mt-6 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 py-3 font-semibold text-white transition hover:bg-blue-700">Place order</button>
        </form>
    </div>
</x-guest-layout>
