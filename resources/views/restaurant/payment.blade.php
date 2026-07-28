<x-guest-layout>

<section class="px-4 py-10 sm:px-6 sm:py-14"><div class="mx-auto max-w-xl">

    <div class="mb-7 text-center sm:text-left"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Secure checkout</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">Complete reservation</h1></div>

    <div
        class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5"
    >

        Reservation expires in

        <span
            id="timer"
            class="font-bold text-red-600"
        >

        </span>

    </div>

    <form
        method="POST"
        action="{{ route('restaurant.pay', [
            'reservation' => $reservation,
            'token' => $accessToken,
        ]) }}"
    >

        @csrf

        <button class="mt-6 flex min-h-12 w-full items-center justify-center rounded-xl bg-green-600 px-8 py-3 font-semibold text-white transition hover:bg-green-700">Pay securely with Paystack</button>

    </form>

    <script>

    let expires = new Date(

        "{{ $reservation->hold_until }}"

    ).getTime();

    let timer = setInterval(function(){

        let now = new Date().getTime();

        let distance = expires - now;

        if(distance < 0){

            clearInterval(timer);

            location.reload();

        }

        let minutes = Math.floor(

            distance / 60000

        );

        let seconds = Math.floor(

            (distance % 60000) / 1000

        );

        document.getElementById(

            'timer'

        ).innerHTML =

            minutes +

            "m " +

            seconds +

            "s";

    },1000);

    </script>

</div></section>

</x-guest-layout>
