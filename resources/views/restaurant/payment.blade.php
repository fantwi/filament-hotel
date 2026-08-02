<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14"><div class="mx-auto max-w-xl">
        <div class="mb-7 text-center sm:text-left"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Secure checkout</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">Complete reservation</h1></div>
        <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5">Reservation expires in <span id="timer" class="font-bold text-red-600"></span></div>
        <div class="mt-6 rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
            <h2 class="text-xl font-bold text-gray-900">Table {{ $reservation->table?->table_number }}</h2><p class="mt-1 text-sm text-gray-600">{{ $reservation->reservation_date?->format('M d, Y') }} at {{ $reservation->reservation_time }}</p>
            @if ($reservation->subtotal !== null)<dl class="mt-6 space-y-2 border-t pt-4 text-sm"><div class="flex justify-between"><dt>Subtotal</dt><dd>GHS {{ number_format($reservation->subtotal, 2) }}</dd></div><div class="flex justify-between text-green-700"><dt>Discount{{ $reservation->promotion_code ? ' ('.$reservation->promotion_code.')' : '' }}</dt><dd>- GHS {{ number_format($reservation->discount, 2) }}</dd></div><div class="flex justify-between"><dt>Service charge</dt><dd>GHS {{ number_format($reservation->service_charge, 2) }}</dd></div><div class="flex justify-between"><dt>VAT</dt><dd>GHS {{ number_format($reservation->vat, 2) }}</dd></div><div class="flex justify-between"><dt>NHIL</dt><dd>GHS {{ number_format($reservation->nhil, 2) }}</dd></div></dl>@endif
            <div class="mt-6 rounded-xl bg-slate-50 px-4 py-4"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total due</p><p class="mt-1 text-2xl font-bold text-gray-900">GHS {{ number_format($reservation->reservation_fee, 2) }}</p></div>
            <form method="POST" action="{{ route('restaurant.pay', ['reservation' => $reservation, 'token' => $accessToken]) }}" class="mt-6">@csrf<button class="flex min-h-12 w-full items-center justify-center rounded-xl bg-green-600 px-8 py-3 font-semibold text-white transition hover:bg-green-700">Pay securely with Paystack</button></form>
        </div>
    </div></section>
    <script>
        let expires = new Date("{{ $reservation->hold_until }}").getTime(); let timer = setInterval(function () { let distance = expires - new Date().getTime(); if (distance < 0) { clearInterval(timer); location.reload(); return; } let minutes = Math.floor(distance / 60000); let seconds = Math.floor((distance % 60000) / 1000); document.getElementById('timer').innerHTML = minutes + 'm ' + seconds + 's'; }, 1000);
    </script>
</x-guest-layout>
