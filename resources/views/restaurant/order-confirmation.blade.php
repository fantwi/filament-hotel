<x-guest-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-12">
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-lg bg-red-100 p-4 text-red-700">{{ session('error') }}</div>
        @endif

        <div class="rounded-xl bg-white p-5 shadow sm:p-8">
            <h1 class="break-words text-2xl font-bold sm:text-3xl">Order {{ $order->order_number }}</h1>
            <p class="mt-2 text-gray-600">Status: <strong>{{ ucfirst($order->status) }}</strong></p>
            <p class="mt-1 text-gray-600">Payment: <strong>{{ ucfirst($order->payment_status) }}</strong></p>
            <p class="mt-6 text-2xl font-bold">Total: GHS {{ number_format($order->total, 2) }}</p>

            @if ($order->payment_status !== 'completed')
                <form action="{{ route('restaurant.orders.pay', $order) }}" method="POST" class="mt-6">
                    @csrf
                    <button class="w-full rounded-lg bg-blue-600 py-3 text-white">Pay with Paystack</button>
                </form>
            @else
                <p class="mt-6 rounded-lg bg-blue-50 p-4 text-blue-700">The kitchen has received your order and will begin preparation shortly.</p>
            @endif
        </div>
    </div>
</x-guest-layout>
