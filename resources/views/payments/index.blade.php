<x-guest-layout>

<div class="max-w-5xl mx-auto py-12">

    <h2 class="text-3xl font-bold mb-8">
        Outstanding Payments
    </h2>

    @forelse($bookings as $booking)

        <div class="bg-white rounded-xl shadow p-6 mb-5">

            <div class="flex justify-between items-center">

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

                <div class="text-right">

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

</div>

</x-guest-layout>