<x-guest-layout>
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14"><div class="mb-7"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Restaurant order</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">Checkout</h1></div>

        <form action="{{ route('restaurant.checkout.store') }}" method="POST" class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
            @csrf
            <h2 class="mb-4 text-xl font-bold">Order summary</h2>
            <div class="space-y-2">
                @foreach ($cartItems as $line)
                    <div class="flex justify-between"><span>{{ $line['quantity'] }} × {{ $line['item']->name }}</span><span>GHS {{ number_format($line['line_total'], 2) }}</span></div>
                @endforeach
            </div>
            <div class="mt-5 border-t pt-4 text-xl font-bold">Total: GHS {{ number_format($totals['total'], 2) }}</div>
            <label for="email" class="mt-6 block font-medium">Email for payment receipt</label>
            <input id="email" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" required class="mt-2 min-h-12 w-full rounded-xl border-gray-300 px-4 text-base">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            <label for="notes" class="mt-6 block font-medium">Order notes</label>
            <textarea id="notes" name="notes" rows="4" class="mt-2 w-full rounded-xl border-gray-300 px-4 py-3 text-base" placeholder="Allergies or special instructions">{{ old('notes') }}</textarea>
            <button class="mt-6 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 py-3 font-semibold text-white transition hover:bg-blue-700">Place order</button>
        </form>
    </div>
</x-guest-layout>
