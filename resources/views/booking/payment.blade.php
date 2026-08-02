<x-guest-layout>

<section class="px-4 py-10 sm:px-6 sm:py-14">
<div class="mx-auto w-full max-w-xl">

<div class="mb-7 text-center sm:text-left">
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Secure checkout</p>
    <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Complete your payment</h1>
    <p class="mt-2 text-sm leading-6 text-gray-600">Your room is temporarily held while you finish payment.</p>
</div>

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
    class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 p-4 sm:p-5"
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

    <div class="flex items-center justify-between gap-4">

        <div>

            <p class="text-sm font-bold text-yellow-800 sm:text-base">
                Room Reserved Temporarily
            </p>

            <p class="text-sm text-yellow-700 mt-1">

                Complete payment before timer expires.

            </p>

        </div>

        <div
            class="shrink-0 text-2xl font-bold tabular-nums text-yellow-800 sm:text-3xl"
            x-text="timeRemaining"
        ></div>

    </div>

</div>

<div class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
<p class="text-sm leading-6 text-gray-600">You will be redirected to Paystack to complete payment securely.</p>

<!-- <form method="POST" action="/booking/payment">
@csrf

<button class="bg-green-600 text-white px-6 py-3 rounded w-full">
Pay & Confirm Booking
</button>

</form> -->

<form method="POST" action="{{ route('booking.pay') }}" class="mt-6">
    @csrf

    <div class="mb-5 rounded-xl bg-slate-50 px-4 py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total due</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">GHS {{ number_format((float) session('booking.total', 0), 2) }}</p>
    </div>

    <button class="flex min-h-12 w-full items-center justify-center rounded-xl bg-green-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2">
        Pay securely with Paystack
    </button>
</form>

<form method="POST" action="{{ route('booking.cancel', $booking) }}" class="mt-3">
    @csrf
    <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl border border-red-200 bg-white px-4 py-3 text-base font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
        Cancel booking
    </button>
</form>

</div>
</div>
</section>

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
