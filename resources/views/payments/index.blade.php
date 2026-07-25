<x-guest-layout>

<section class="px-4 py-10 sm:px-6 sm:py-14"><div class="mx-auto max-w-5xl">

    <div class="mb-8"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Guest account</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Outstanding payments</h1></div>

    @forelse($bookings as $booking)

        <div class="mb-5 rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-6">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h3 class="font-bold text-lg">
                        {{ $booking->room->roomType->name }}
                    </h3>

                    <p class="text-gray-500">
                        Room:
                        {{ $booking->room->room_number }}
                    </p>

                    <p class="text-gray-500">
                        Check-in:
                        {{ $booking->check_in }}
                    </p>

                    <p class="text-gray-500">
                        Check-out:
                        {{ $booking->check_out }}
                    </p>

                </div>

                <div class="text-left sm:text-right">

                    <p class="font-bold text-xl mb-3">
                        GHS
                        {{ number_format($booking->total_price, 2) }}
                    </p>

                    <!-- <a
                        href="{{ route('booking.payment') }}"
                        class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-700 transition"
                    >
                        Complete Payment
                    </a> -->

                    <!-- @if($booking->payment_status === 'pending')

                        <a
                            href="{{ route('booking.payment', $booking->id) }}"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl bg-red-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                        >
                            Complete Payment
                        </a>

                    @elseif($booking->payment_status === 'paid')

                        <span
                            class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium"
                        >
                            ✓ Fully Paid
                        </span>

                    @else

                        <span
                            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium"
                        >
                            {{ ucfirst($booking->payment_status ?? 'Unknown') }}
                        </span>

                    @endif -->

                    @if(
                        in_array(
                            $booking->payment_status,
                            ['pending', 'unpaid']
                        )
                    )

                        <a
                            href="{{ route('booking.payment', $booking->id) }}"
                            class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-700 transition"
                        >
                            Complete Payment
                        </a>

                    @elseif($booking->payment_status === 'paid')

                        <span
                            class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium"
                        >
                            ✓ Fully Paid
                        </span>

                    @else

                        <span
                            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium"
                        >
                            {{ ucfirst($booking->payment_status) }}
                        </span>

                    @endif

                </div>

            </div>

        </div>

    @empty

        <div class="bg-green-50 border border-green-200 rounded-xl p-6">

            <p class="text-green-700">
                No outstanding payments.
            </p>

        </div>

    @endforelse

</div></section>

</x-guest-layout>
