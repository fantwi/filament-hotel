<x-guest-layout>

@include('partials.guest-nav')

<div class="max-w-4xl mx-auto py-16">

    <h1 class="text-3xl font-bold">

        Complete Reservation

    </h1>

    <div
        class="bg-yellow-100
        rounded-xl
        p-6
        mt-8"
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
        action="{{ route('restaurant.pay', $reservation) }}"
    >

        @csrf

        <button
            class="mt-8 bg-green-600 hover:bg-green-700 text-white px-8 py-4 rounded-xl"
        >

            Pay with Paystack

        </button>

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