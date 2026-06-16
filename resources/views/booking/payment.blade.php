<x-guest-layout>

<div class="max-w-xl mx-auto py-12">

<h2 class="text-2xl font-bold mb-6">
Payment
</h2>

<!-- <p class="text-red-500 mb-4">
    Hold Until:
    {{ $booking?->hold_until }}
</p> -->

<!-- <div class="bg-red-50 p-4 rounded mb-4 text-sm">

    <p>
        Hold Until:
        {{ $booking?->hold_until }}
    </p>

    <p>
        Timestamp:
        {{ optional($booking?->hold_until)?->timestamp * 1000 }}
    </p>

    <p>
        Server Now:
        {{ now() }}
    </p>

    <p>
        Server Timestamp:
        {{ now()->timestamp * 1000 }}
    </p>

</div> -->

<div
    x-data="holdTimer()"
    class="bg-yellow-50 border border-yellow-200 rounded-2xl p-5 mb-8"
>

    <!-- Temporary debug info -->
    <!-- <div class="mt-4 text-xs text-red-600 space-y-1">

        <p>
            Expires At:
            <span x-text="expiresAt"></span>
        </p>

        <p>
            Browser Time:
            <span x-text="Date.now()"></span>
        </p>

        <p>
            Difference:
            <span x-text="Number(expiresAt) - Date.now()"></span>
        </p>

    </div> -->

    <div class="flex items-center justify-between">

        <div>

            <p class="font-bold text-yellow-800">
                Room Reserved Temporarily
            </p>

            <p class="text-sm text-yellow-700 mt-1">

                Complete payment before timer expires.

            </p>

        </div>

        <div
            class="text-3xl font-bold text-yellow-800"
            x-text="timeRemaining"
        ></div>

    </div>

</div>

<p class="mb-6 text-gray-600">
Demo payment (Flutterwave comes later)
</p>

<!-- <form method="POST" action="/booking/payment">
@csrf

<button class="bg-green-600 text-white px-6 py-3 rounded w-full">
Pay & Confirm Booking
</button>

</form> -->

<form method="POST" action="{{ route('booking.pay') }}">
    @csrf

    <p class="mb-4 text-lg font-semibold">
        Total: GHS {{ session('booking.total') }}
    </p>

    <button class="bg-green-600 text-white px-6 py-3 rounded w-full">
        Pay with Paystack
    </button>
</form>

</div>

<script>

function holdTimer() {

    return {

        // expiresAt:
        //     "{{ optional($booking?->hold_until)?->toIso8601String() }}",

        expiresAt:
            "{{ optional($booking?->hold_until)?->timestamp * 1000 }}",

        timeRemaining: '10:00',

        interval: null,

        init() {

            // no timer value
            if (!this.expiresAt) {

                this.timeRemaining =
                    'Expired';

                return;
            }

            // const expiry =
            //     Date.parse(
            //         this.expiresAt
            //     );

            const expiry =
                Number(
                    this.expiresAt
                );

            // invalid date protection
            if (isNaN(expiry)) {

                this.timeRemaining =
                    'Invalid Timer';

                return;
            }

            this.updateTimer();

            this.interval =
                setInterval(() => {

                    this.updateTimer();

                }, 1000);
        },

        updateTimer() {

            const now =
                Date.now();

            // const expiry =
            //     Date.parse(
            //         this.expiresAt
            //     );

            const expiry =
                Number(
                    this.expiresAt
                );

            const diff =
                expiry - now;

            if (diff <= 0) {

                clearInterval(
                    this.interval
                );

                this.timeRemaining =
                    'Expired';

                window.location.href =
                    '/booking/expired';

                return;
            }

            const minutes =
                Math.floor(
                    diff /
                    (1000 * 60)
                );

            const seconds =
                Math.floor(
                    (
                        diff %
                        (1000 * 60)
                    ) / 1000
                );

            this.timeRemaining =
                `${minutes}:${seconds
                    .toString()
                    .padStart(2, '0')}`;
        }

    }

}

</script>

</x-guest-layout>