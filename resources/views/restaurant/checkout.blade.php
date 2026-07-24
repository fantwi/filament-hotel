<x-guest-layout>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-12">
        <h1 class="mb-8 text-4xl font-bold">Checkout</h1>

        <form action="{{ route('restaurant.checkout.store') }}" method="POST" class="rounded-xl bg-white p-5 shadow sm:p-6">
            @csrf
            <h2 class="mb-4 text-xl font-bold">Order summary</h2>
            <div class="space-y-2">
                @foreach ($cartItems as $line)
                    <div class="flex justify-between"><span>{{ $line['quantity'] }} × {{ $line['item']->name }}</span><span>GHS {{ number_format($line['line_total'], 2) }}</span></div>
                @endforeach
            </div>
            <div class="mt-5 border-t pt-4 text-xl font-bold">Total: GHS {{ number_format($totals['total'], 2) }}</div>
            <label for="email" class="mt-6 block font-medium">Email for payment receipt</label>
            <input id="email" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" required class="mt-2 w-full rounded border-gray-300">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            <label for="notes" class="mt-6 block font-medium">Order notes</label>
            <textarea id="notes" name="notes" rows="4" class="mt-2 w-full rounded border-gray-300" placeholder="Allergies or special instructions">{{ old('notes') }}</textarea>
            <button class="mt-6 w-full rounded-lg bg-blue-600 py-3 text-white">Place Order</button>
        </form>
    </div>
</x-guest-layout>
