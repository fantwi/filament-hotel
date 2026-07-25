<x-guest-layout>
<section class="px-4 py-10 sm:px-6 sm:py-14">
    <div class="mx-auto w-full max-w-md rounded-2xl bg-white p-5 text-center shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Secure payment</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Pay for your booking</h1>
        <div class="mt-6 rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total due</p><p class="mt-1 text-2xl font-bold text-gray-900">GHS {{ number_format($booking->total_price, 2) }}</p></div>
        <button type="button" onclick="payWithPaystack()" class="mt-6 flex min-h-12 w-full items-center justify-center rounded-xl bg-green-600 px-4 py-3 font-semibold text-white transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2">Pay securely with Paystack</button>
    </div>
</section>

<script src="https://js.paystack.co/v1/inline.js"></script>

<script>
function payWithPaystack() {

    var handler = PaystackPop.setup({
        key: "{{ config('services.paystack.key') }}",
        email: "{{ auth()->user()->email }}",
        amount: {{ $booking->total_price * 100 }},
        callback: function(response) {
            window.location.href = "/payment-success/{{ $booking->id }}?ref=" + response.reference;
        }
    });

    handler.openIframe();
}
</script>

</x-guest-layout>
