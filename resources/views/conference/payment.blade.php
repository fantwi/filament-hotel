<x-guest-layout>

<div class="max-w-xl mx-auto py-12">

    <h2
        class="text-2xl font-bold mb-6"
    >

        Conference Payment

    </h2>

    <div
        x-data="holdTimer()"
        class="bg-yellow-50
        border border-yellow-200
        rounded-2xl
        p-5 mb-8"
    >

        <div class="flex items-center justify-between">

            <div>

                <p
                    class="font-bold
                    text-yellow-800"
                >

                    Conference Room
                    Reserved Temporarily

                </p>

                <p
                    class="text-sm
                    text-yellow-700
                    mt-1"
                >

                    Complete payment
                    before timer expires.

                </p>

            </div>

            <div
                class="text-3xl
                font-bold
                text-yellow-800"

                x-text="timeRemaining"
            >

            </div>

        </div>

    </div>

    <div
        class="bg-yellow-50
        border border-yellow-200
        rounded-xl
        p-5 mb-6"
    >

        <h3
            class="font-bold"
        >

            {{ $booking
                ->room
                ->name }}

        </h3>

        <p>

            Date:
            {{
                $booking
                ->booking_date
                ->format('M d, Y')
            }}

        </p>

        <p>

            Time:
            {{
                $booking
                ->start_time
            }}
            -
            {{
                $booking
                ->end_time
            }}

        </p>

        <p>

            Attendees:
            {{
                $booking
                ->attendees
            }}

        </p>

        <p
            class="text-xl
            font-bold mt-4"
        >

            GHS
            {{
                number_format(
                    $booking
                        ->total_price,
                    2
                )
            }}

        </p>

    </div>

    <form
        method="POST"
        action="{{ route(
            'conference.pay',
            $booking->id
        ) }}"
    >

        @csrf

        <button
            type="submit"
            class="bg-green-600
            text-white
            px-6 py-3
            rounded-lg
            w-full
            hover:bg-green-700
            transition"
        >

            Pay with Paystack

        </button>

    </form>

</div>

<script>

function holdTimer() {

    return {

        expiresAt:
            "{{ optional(
                $booking
                ?->hold_until
            )?->toIso8601String() }}",

        timeRemaining:
            '10:00',

        interval:
            null,

        init() {

            if (
                !this.expiresAt
            ) {

                this.timeRemaining =
                    'Expired';

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
                new Date()
                .getTime();

            const expiry =
                Date.parse(
                    this.expiresAt
                );

            const diff =
                expiry - now;

            if (
                diff <= 0
            ) {

                clearInterval(
                    this.interval
                );

                this.timeRemaining =
                    'Expired';

                window.location.href =
                    "{{ route(
                        'conference.expired'
                    ) }}";

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

                    .padStart(
                        2,
                        '0'
                    )}`;
        }
    }
}

</script>

</x-guest-layout>