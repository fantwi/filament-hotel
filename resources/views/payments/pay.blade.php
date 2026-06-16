<x-app-layout>

<div class="max-w-md mx-auto py-6 text-center">

    <h2 class="text-xl font-bold mb-4">Pay for Booking</h2>

    <p>Total: GHS {{ number_format($booking->total_price, 2) }}</p>

    <button onclick="payWithPaystack()"
        class="bg-green-600 text-white px-4 py-2 rounded mt-4">
        Pay Now
    </button>

</div>

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

</x-app-layout>