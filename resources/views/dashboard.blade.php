<x-guest-layout>

@php
    // ✅ Ensure bookings is always a collection
    $bookings = $bookings ?? collect();
    $user = auth()->user();
@endphp

<div class="max-w-7xl mx-auto py-6 px-4">
<!-- <div class="max-w-7xl mx-auto py-6 px-4 border-4 border-red-500 bg-yellow-100"> -->

    <!-- 🔴 Outstanding Balance Alert -->
    <!-- @php
    $outstandingBalance =
        $bookings
            ->where('payment_status', 'unpaid')
            ->sum('total_price');
    @endphp -->

    <!-- @php
    $outstandingBalance =
        $bookings
            ->where(
                'payment_status',
                'pending'
            )
            ->sum('total_price');
    @endphp -->

    @auth

    <div
        class="bg-white rounded-2xl shadow p-6 mb-8"
    >

        <div
            class="flex items-center gap-5"
        >

            <div
                class="w-20 h-20 rounded-full
                bg-blue-100
                flex items-center
                justify-center
                text-2xl font-bold
                text-blue-700"
            >

                {{ strtoupper(
                    substr(
                        auth()->user()
                            ?->first_name ?? 'G',
                        0,
                        1
                    )
                ) }}

            </div>

            <div class="flex-1">

                <h2
                    class="text-2xl font-bold"
                >

                    Welcome,
                    {{ auth()->user()
                        ?->first_name }}

                </h2>

                <p class="text-gray-500">

                    {{ auth()->user()
                        ?->email }}

                </p>

                <p class="text-gray-500">

                    Phone:
                    {{
                        auth()->user()
                        ?->phone_number
                        ?? 'N/A'
                    }}

                </p>

                <p class="text-gray-500">

                    Guest ID:
                    {{
                        auth()->user()
                        ?->guest?->id
                    }}

                </p>

            </div>

            <div>

                <span
                    class="bg-green-100
                    text-green-700
                    px-4 py-2 rounded-full"
                >

                    Logged In

                </span>

            </div>

        </div>

    </div>

    @endauth

    @php
    $outstandingBalance =
        $bookings
            ->where(
                'payment_status',
                'pending'
            )
            ->filter(function ($booking) {
                return
                    $booking->hold_status !== 'expired';
            })
            ->sum('total_price');
    @endphp

    <!-- @if ($outstandingBalance > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl p-5 mb-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            <div class="text-red-700">

                <p class="font-semibold">
                    Outstanding Balance
                </p>
                <p>
                    You have an unpaid balance of 
                    <strong>GHS {{ number_format($outstandingBalance, 2) }}</strong>.
                </p>
            </div>

            <div>
                <a href="/payments"
                    class="inline-block bg-red-600 text-white px-5 py-3 rounded-lg hover:bg-red-700 transition">
                    Complete Payment
                </a>
            </div>

        </div>
    </div>
    @endif -->

    @if ($outstandingBalance > 0)

        <div class="bg-red-50 border border-red-200 rounded-xl p-5 mb-6">

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                <div class="text-red-700">

                    <p class="font-semibold">
                        Outstanding Balance
                    </p>

                    <p>
                        You have an unpaid balance of
                        <strong>
                            GHS {{ number_format($outstandingBalance, 2) }}
                        </strong>.
                    </p>

                </div>

                <div>
                    <!-- <a href="/payments" -->
                    <a
                        href="{{ route('payments.index') }}"
                        class="inline-block bg-red-600 text-white px-5 py-3 rounded-lg hover:bg-red-700 transition"
                    >

                        Complete Payment

                    </a>
                </div>

            </div>

        </div>

    @else

        <div class="bg-green-50 border border-green-200 rounded-xl p-5 mb-6">

            <p class="text-green-700 font-medium text-center">
                ✅ All payments completed. No outstanding balance.
            </p>

        </div>

    @endif

    <!-- 📊 STATS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <!-- Total Bookings -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border hover:shadow-md transition flex items-center gap-4">
            <div class="text-2xl">📅</div>
            <div>
                <p class="text-gray-500 dark:text-gray-300 text-sm">Total Bookings</p>
                <h2 class="text-2xl font-bold text-blue-800 dark:text-white">
                    {{ $totalBookings }}
                </h2>
            </div>
        </div>

        <!-- Total Hotel Room Bookings -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border hover:shadow-md transition flex items-center gap-4">
            <div class="text-2xl">📅</div>
            <div>
                <p class="text-gray-500 dark:text-gray-300 text-sm">Hotel Room Bookings</p>
                <h2 class="text-2xl font-bold text-blue-800 dark:text-white">
                    {{ $hotelBookings->count() }}
                </h2>
            </div>
        </div>

        <!-- Total Hotel Room Bookings -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border hover:shadow-md transition flex items-center gap-4">
            <div class="text-2xl">📅</div>
            <div>
                <p class="text-gray-500 dark:text-gray-300 text-sm">Conference Room Bookings</p>
                <h2 class="text-2xl font-bold text-blue-800 dark:text-white">
                    {{ $conferenceBookings->count() }}
                </h2>
            </div>
        </div>

        <!-- Total Hotel Room Bookings -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border hover:shadow-md transition flex items-center gap-4">
            <div class="text-2xl">📅</div>
            <div>
                <p class="text-gray-500 dark:text-gray-300 text-sm">Total Confirmed Bookings</p>
                <h2 class="text-2xl font-bold text-blue-800 dark:text-white">
                    {{ $totalConfirmedBookings }}
                </h2>
            </div>
        </div>

        <!-- Total Spent -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border hover:shadow-md transition flex items-center gap-4">
            <div class="text-2xl">💰</div>
            <div>
                <p class="text-gray-500 dark:text-gray-300 text-sm">Total Spent</p>
                <h2 class="text-2xl font-bold text-green-600">
                    <!-- GHS {{ number_format($bookings->where('hold_status', 'confirmed')->sum('total_price'), 2) }} -->
                    GHS {{ number_format($totalSpent, 2)}}
                </h2>
            </div>
        </div>

        <!-- Outstanding Balance -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border hover:shadow-md transition flex items-center gap-4">
            <div class="text-2xl">⚠️</div>
            <div>
                <p class="text-gray-500 dark:text-gray-300 text-sm">Outstanding Balance</p>
                <h2 class="text-2xl font-bold text-red-600">
                    GHS {{ number_format($outstandingBalance, 2) }}
                </h2>
            </div>
        </div>

    </div>

</div>

<!-- 📋 BOOKINGS LIST -->
<!-- <div class="max-w-7xl mx-auto py-6 px-4"> -->
<div class="bg-white rounded-2xl shadow p-6 mt-8">
<!-- <div class="max-w-7xl mx-auto py-6 px-4 border-4 border-red-500 bg-yellow-100"> -->

    <h2 class="text-2xl font-bold mb-6">My Bookings</h2>

    <h3 class="text-xl font-semibold text-blue-700 mb-4">Hotel Bookings</h3>
    @forelse(
        $hotelBookings->where('status', 'confirmed')
        as $booking
    )

    <div
        class="border
        rounded-xl
        p-5
        mb-4"
    >

        <div
            class="flex
            justify-between
            items-start"
        >

            <div>

                <h4
                    class="font-bold
                    text-lg"
                >

                    {{
                        $booking
                            ->room
                            ?->roomType
                            ?->name
                    }}

                </h4>

                <p
                    class="text-gray-600"
                >

                    Check In:

                    {{
                        \Carbon\Carbon::parse(
                            $booking
                                ->check_in
                        )
                        ->format(
                            'M d, Y'
                        )
                    }}

                </p>

                <p
                    class="text-gray-600"
                >

                    Check Out:

                    {{
                        \Carbon\Carbon::parse(
                            $booking
                                ->check_out
                        )
                        ->format(
                            'M d, Y'
                        )
                    }}

                </p>

                <p
                    class="font-semibold"
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

                @if(
                    $booking
                        ->payment_status
                    === 'pending'

                    &&

                    $booking
                        ->hold_status
                    !== 'expired'
                )

                <a

                    href="{{
                        route(
                            'booking.payment',
                            $booking->id
                        )
                    }}"

                    class="inline-block
                    mt-3
                    bg-red-600
                    text-white
                    px-4 py-2
                    rounded-lg
                    hover:bg-red-700"

                >

                    Complete Payment

                </a>

                @endif

                <form

                    method="POST"

                    action="{{
                        route(
                            'booking.cancel',
                            $booking->id
                        )
                    }}"

                    class="mt-3"
                >

                    @csrf

                    <button

                        onclick="
                            return confirm(
                                'Cancel booking?'
                            )
                        "

                        class="bg-gray-600
                        text-white
                        px-4 py-2
                        rounded-lg"

                    >

                        Cancel Booking

                    </button>

                </form>

                <a

                    href="{{
                        route(
                            'booking.invoice',
                            $booking->id
                        )
                    }}"

                    class="inline-block
                    mt-3
                    bg-blue-600
                    text-white
                    px-4 py-2
                    rounded-lg"

                >

                    Download Invoice

                </a>

            </div>

            <!-- <span
                class="bg-blue-100
                text-blue-700
                px-4 py-1
                rounded-full
                text-sm"
            >

                {{
                    ucfirst(
                        $booking
                            ->status
                    )
                }}

            </span> -->

            <span

                class="px-4
                py-1
                rounded-full
                text-sm
                font-semibold

                @if(
                    $booking->status
                    === 'confirmed'
                )

                    bg-green-100
                    text-green-700

                @elseif(
                    $booking->status
                    === 'pending'
                )

                    bg-yellow-100
                    text-yellow-700

                @elseif(
                    $booking->status
                    === 'cancelled'
                )

                    bg-red-100
                    text-red-700

                @elseif(
                    $booking->status
                    === 'expired'
                )

                    bg-gray-100
                    text-gray-700

                @endif
            "

            >

                {{
                    ucfirst(
                        $booking
                            ->status
                    )
                }}

            </span>

        </div>

    </div>

    @empty

    <p class="text-gray-500">

        No hotel bookings found.

    </p>

    @endforelse

    <h3
        class="text-xl
        font-semibold
        text-purple-700
        mt-10
        mb-4"
    >

        Conference Room Bookings

    </h3>
    @forelse(
        $conferenceBookings->where('status', 'confirmed')
        as $booking
    )

    <div
        class="border
        rounded-xl
        p-5
        mb-4"
    >

        <div
            class="flex
            justify-between
            items-start"
        >

            <div>

                <h4
                    class="font-bold
                    text-lg"
                >

                    {{
                        $booking
                            ->room
                            ?->name
                    }}

                </h4>

                <p
                    class="text-gray-600"
                >

                    Date:

                    {{
                        $booking
                            ->booking_date
                            ?->format(
                                'M d, Y'
                            )
                    }}

                </p>

                <p
                    class="text-gray-600"
                >

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

                <p
                    class="text-gray-600"
                >

                    Attendees:

                    {{
                        $booking
                            ->attendees
                    }}

                </p>

                <p
                    class="font-semibold"
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

                @if(
                    $booking
                        ->payment_status
                    === 'pending'
                )

                <a

                    href="{{
                        route(
                            'conference.payment',
                            $booking->id
                        )
                    }}"

                    class="inline-block
                    mt-3
                    bg-red-600
                    text-white
                    px-4 py-2
                    rounded-lg
                    hover:bg-red-700"

                >

                    Complete Payment

                </a>

                @endif

                <form

                    method="POST"

                    action="{{
                        route(
                            'conference.cancel',
                            $booking->id
                        )
                    }}"

                    class="mt-3"
                >

                    @csrf

                    <button

                        onclick="
                            return confirm(
                                'Cancel booking?'
                            )
                        "

                        class="bg-gray-600
                        text-white
                        px-4 py-2
                        rounded-lg"

                    >

                        Cancel Booking

                    </button>

                </form>

                <a

                    href="{{
                        route(
                            'conference.invoice',
                            $booking->id
                        )
                    }}"

                    class="inline-block
                    mt-3
                    bg-blue-600
                    text-white
                    px-4 py-2
                    rounded-lg"

                >

                    Download Invoice

                </a>


            </div>

            <!-- <span
                class="bg-purple-100
                text-purple-700
                px-4 py-1
                rounded-full
                text-sm"
            >

                {{
                    ucfirst(
                        $booking
                            ->status
                    )
                }}

            </span> -->

            <span

                class="px-4
                py-1
                rounded-full
                text-sm
                font-semibold

                @if(
                    $booking->status
                    === 'confirmed'
                )

                    bg-green-100
                    text-green-700

                @elseif(
                    $booking->status
                    === 'pending'
                )

                    bg-yellow-100
                    text-yellow-700

                @elseif(
                    $booking->status
                    === 'cancelled'
                )

                    bg-red-100
                    text-red-700

                @endif
            "

            >

                {{
                    ucfirst(
                        $booking
                            ->status
                    )
                }}

            </span>

        </div>

    </div>

    @empty

    <p class="text-gray-500">

        No conference bookings found.

    </p>

    @endforelse

    <!-- @forelse ($bookings as $booking) -->

        <div class="bg-white dark:bg-gray-800 p-4 mb-4 text-gray-200 rounded shadow">

            <div class="flex flex-col md:flex-row md:justify-between gap-6">

                <!-- Left -->
                <div>
                    <p><strong>Room:</strong> {{ $booking->room?->room_number ?? 'N/A' }}</p>

                    <p><strong>Check-in:</strong> 
                        {{ \Carbon\Carbon::parse($booking->check_in)->format('d M Y') }}
                    </p>

                    <p><strong>Check-out:</strong> 
                        {{ \Carbon\Carbon::parse($booking->check_out)->format('d M Y') }}
                    </p>

                    <p>
                        <strong>Status:</strong>
                        <!-- <span class="px-2 py-1 text-sm rounded bg-blue-100 dark:bg-blue-700 text-blue-800 dark:text-white">
                            {{ ucfirst($booking->status) }}
                        </span> -->
                        <span

                            class="px-4
                            py-1
                            rounded-full
                            text-sm
                            font-semibold

                            @if(
                                $booking->status
                                === 'confirmed'
                            )

                                bg-green-100
                                text-green-700

                            @elseif(
                                $booking->status
                                === 'pending'
                            )

                                bg-yellow-100
                                text-yellow-700

                            @elseif(
                                $booking->status
                                === 'cancelled'
                            )

                                bg-red-100
                                text-red-700

                            @elseif(
                                $booking->status
                                === 'expired'
                            )

                                bg-gray-100
                                text-gray-700

                            @endif
                        "

                        >

                            {{
                                ucfirst(
                                    $booking
                                        ->status
                                )
                            }}

                        </span>
                    </p>
                </div>

                <!-- Right -->
                <div>
                    <p><strong>Total:</strong> GHS {{ number_format($booking->total_price ?? 0, 2) }}</p>
                    <p><strong>Paid:</strong> 
                        @if($booking->payment_status === 'paid')
                            GHS {{ number_format($booking->total_price ?? 0, 2) }}
                        @else
                            GHS 0.00
                        @endif
                    </p>
                    @php
                    $amountPaid =
                        $booking->payments
                            ->where('payment_status', 'paid')
                            ->sum('amount');

                    $balance =
                        ($booking->total_price ?? 0)
                        - $amountPaid;
                    @endphp

                    <p>
                        <strong>Balance:</strong>

                        GHS {{ number_format(max($balance, 0), 2) }}
                    </p>
                <br>
                @if(
                    in_array(
                        $booking->payment_status,
                        ['pending', 'unpaid']
                    )
                    &&
                    $booking->hold_status !==
                    'expired'
                )
                    <a
                        href="{{ route(
                            'booking.payment',
                            $booking->id
                        ) }}"
                        class="inline-flex items-center gap-2 bg-red-600 text-white px-5 py-3 rounded-xl hover:bg-red-700 transition shadow-sm"
                    >
                        Complete Payment
                    </a>
                @elseif(
                    $booking->payment_status ===
                    'paid'
                )
                    <span
                        class="inline-flex items-center bg-green-100 text-green-700 px-4 py-2 rounded-lg font-medium"
                    >
                        ✓ Fully Paid
                    </span>
                @elseif(
                    $booking->hold_status ===
                    'expired'
                )
                    <span
                        class="inline-flex items-center bg-gray-100 text-gray-600 px-4 py-2 rounded-lg font-medium"
                    >
                        Reservation Expired
                    </span>
                @endif

            </div>

            </div>

            <!-- 💳 Payments -->
            <div class="mt-3">
                <strong>Payments:</strong>

                @if (($booking->payments ?? collect())->isEmpty())
                    <p class="text-gray-400 dark:text-gray-300">No payments yet</p>
                @else
                    <ul>
                        @foreach ($booking->payments ?? [] as $payment)
                            <li>
                                GHS {{ number_format($payment->amount, 2) }} 
                                ({{ ucfirst($payment->method) }})
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- 📄 Invoice -->
            @if (!empty($booking->invoice_number))
                <div class="mt-3">
                    <a href="{{ url('/invoice/'.$booking->id) }}"
                       target="_blank"
                       class="text-blue-600 underline">
                        Download Invoice
                    </a>
                </div>
            @endif

        </div>

    <!-- @empty -->

        <!-- 🛏️ EMPTY STATE -->
        <!-- <div class="bg-white dark:bg-gray-800 p-8 rounded shadow text-center">

            <div class="text-5xl mb-4">🛏️</div>

            <h3 class="text-xl font-semibold mb-2 text-blue-800 dark:text-white">
                No bookings yet
            </h3>

            <p class="text-blue-600 dark:text-blue-300 mb-4">
                @if ($user && $user->created_at->gt(now()->subDay()))
                    Welcome {{ $user->first_name ?? 'Guest' }} 👋 — your journey starts here!
                @else
                    You haven't made any bookings yet.
                @endif
            </p>

            <p class="text-blue-600 dark:text-blue-300 mb-4">
                Start your first booking and enjoy your stay with us.
            </p>

            -->

            <!-- 🚀 QUICK ACTIONS -->
            <!-- <div class="flex justify-center gap-4 mt-6">

                <a href="/rooms"
                   class="flex items-center gap-2 bg-blue-600 dark:bg-blue-700 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition shadow">
                    🏨 <span>Book a Room</span>
                </a>

                <a href="/contact"
                   class="flex items-center gap-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white px-5 py-2.5 rounded-lg hover:bg-gray-300 transition">
                    📞 <span>Contact Hotel</span>
                </a>

            </div>

        </div> -->

    <!-- @endforelse -->

</div>

</x-guest-layout>
