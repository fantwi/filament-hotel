<x-guest-layout>

<style>[x-cloak] { display: none !important; }</style>

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

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border hover:shadow-md transition flex items-center gap-4">
            <div class="text-2xl">🍽️</div>
            <div>
                <p class="text-gray-500 dark:text-gray-300 text-sm">Restaurant Food Orders</p>
                <h2 class="text-2xl font-bold text-orange-600 dark:text-white">{{ $totalRestaurantOrders ?? 0 }}</h2>
            </div>
        </div>

        <!-- Total Conference Room Bookings -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border hover:shadow-md transition flex items-center gap-4">
            <div class="text-2xl">📅</div>
            <div>
                <p class="text-gray-500 dark:text-gray-300 text-sm">Conference Room Bookings</p>
                <h2 class="text-2xl font-bold text-blue-800 dark:text-white">
                    {{ $conferenceBookings->count() }}
                </h2>
            </div>
        </div>

        <!-- Total Restaurant Reservations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border hover:shadow-md transition flex items-center gap-4">
            <div class="text-2xl">📅</div>
            <div>
                <p class="text-gray-500 dark:text-gray-300 text-sm">Restaurant Reservations</p>
                <h2  class="text-2xl font-bold text-blue-800 dark:text-white"> 
                    {{ $totalRestaurantReservations }}
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
<!-- <div class="bg-white rounded-2xl shadow p-6 mt-8"> -->
<!-- <div class="max-w-7xl mx-auto py-6 px-4 border-4 border-red-500 bg-yellow-100"> -->

<div
    x-data="{ tab: 'hotel' }"
    class="bg-white rounded-2xl shadow p-6 mt-8"
>

    <h2 class="text-2xl font-bold mb-6">My Bookings</h2>

    <!-- <h2 class="text-2xl font-bold mb-6">My Bookings</h2> -->

    <div class="flex flex-wrap gap-3 mb-8">

        <button
            @click="tab='hotel'"
            :class="tab == 'hotel'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700'"
            class="px-5 py-2 rounded-lg transition"
        >
            Hotel Room Bookings
        </button>

        <button
            @click="tab='conference'"
            :class="tab == 'conference'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700'"
            class="px-5 py-2 rounded-lg transition"
        >
            Conference Room Bookings
        </button>

        <button
            @click="tab='restaurant'"
            :class="tab == 'restaurant'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700'"
            class="px-5 py-2 rounded-lg transition"
        >
            Restaurant Reservations
        </button>

        <button
            @click="tab='restaurant-orders'"
            :class="tab == 'restaurant-orders'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700'"
            class="px-5 py-2 rounded-lg transition"
        >
            Restaurant Food Orders
        </button>

    </div>

    <div x-show="tab == 'hotel'">
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
    </div>

    <div x-show="tab == 'conference'">
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

    </div>

    <div x-show="tab == 'restaurant'">

        <h3 class="text-xl font-semibold text-green-700 mb-4">

            Restaurant Reservations

        </h3>

        <table class="min-w-full">

            <thead>

                <tr>

                    <th>Date</th>

                    <th>Time</th>

                    <th>Restaurant</th>

                    <th>Table</th>

                    <th>Status</th>

                    <th>Payment</th>

                    <th>Action</th>

                </tr>

            </thead>

            <tbody>
                @forelse($restaurantReservations as $reservation)

                    <div class="border rounded-xl p-5 mb-4">

                        <div class="flex justify-between items-start">

                            <div>

                                <h4 class="font-bold text-lg">

                                    {{ $reservation->restaurant->name }}

                                </h4>

                                <p>

                                    Table:

                                    {{ $reservation->table->table_number }}

                                </p>

                                <p>

                                    Date:

                                    {{ $reservation->reservation_date->format('M d, Y') }}

                                </p>

                                <p>

                                    Time:

                                    {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}

                                </p>

                                <p>

                                    Reservation Fee:

                                    GHS {{ number_format($reservation->reservation_fee,2) }}

                                </p>

                                @if(
                                    $reservation->payment_status != 'completed'
                                    &&
                                    $reservation->hold_status != 'expired'
                                )

                                    <a
                                        href="{{ route('restaurant.payment',$reservation) }}"
                                        class="inline-block mt-3 bg-red-600 text-white px-4 py-2 rounded-lg"
                                    >

                                        Complete Payment

                                    </a>

                                @endif

                                <a
                                    href="{{ route('restaurant.reservations.show', $reservation) }}"
                                    class="text-blue-600 font-medium hover:underline"
                                >
                                    View
                                </a>

                                @if($reservation->status === 'pending')

                                    <form
                                        action="{{ route('restaurant.reservations.cancel', $reservation) }}"
                                        method="POST"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            class="text-red-600 font-medium hover:underline"
                                        >
                                            Cancel
                                        </button>

                                    </form>

                                @endif

                            </div>

                            @php

                                $statusColor = match($reservation->status){

                                    'confirmed' => 'bg-green-100 text-green-700',

                                    'pending' => 'bg-yellow-100 text-yellow-700',

                                    'cancelled' => 'bg-red-100 text-red-700',

                                    default => 'bg-gray-100 text-gray-700',

                                };

                            @endphp

                            <span class="px-4 py-1 rounded-full {{ $statusColor }}">

                                {{ ucfirst($reservation->status) }}

                            </span>

                        </div>

                    </div>

                @empty

                    <p class="text-gray-500">

                        No restaurant reservations found.

                    </p>

                @endforelse

                <!-- @forelse($restaurantReservations as $reservation)

                    <tr>

                        <td>

                            {{ $reservation->reservation_date->format('d M Y') }}

                        </td>

                        <td>

                            {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}

                        </td>

                        <td>

                            {{ $reservation->restaurant->name }}

                        </td>

                        <td>

                            {{ $reservation->table->table_number }}

                        </td>

                        <td>

                            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700">

                                {{ ucfirst($reservation->status) }}

                            </span>

                        </td>

                        <td>

                            {{ ucfirst($reservation->payment_status) }}

                        </td>

                        <td>
 -->
                            <!-- Actions -->
<!-- 
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="7">

                            No restaurant reservations.

                        </td>

                    </tr>

                @endforelse -->

            </tbody>
        </table>

    </div>

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

    <div x-show="tab == 'restaurant-orders'" x-cloak x-transition.opacity>
        <div class="mb-6 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-orange-700">Restaurant Food Orders</h3>
            <a href="{{ route('restaurant.menu') }}" class="rounded-lg bg-orange-600 px-4 py-2 text-white">Order Food</a>
        </div>

        <div class="space-y-4">
            @forelse ($restaurantOrders as $order)
                <div class="border rounded-xl p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h4 class="font-bold">Order {{ $order->order_number }}</h4>
                            <p class="text-sm text-gray-600">{{ $order->items->sum('quantity') }} item(s) · GHS {{ number_format($order->total, 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p>Status: <strong>{{ ucfirst($order->status) }}</strong></p>
                            <p>Payment: <strong>{{ ucfirst($order->payment_status) }}</strong></p>
                        </div>
                    </div>

                    @php
                        $guestStatusMessage = match ($order->status) {
                            'pending' => 'Your order is awaiting payment or confirmation.',
                            'confirmed' => 'Your order has been received by the restaurant.',
                            'preparing' => 'The kitchen is preparing your order.',
                            'ready' => 'Your order is ready.',
                            'served' => 'Your order has been served.',
                            'cancelled' => 'This order was cancelled.',
                            default => 'Order status unavailable.',
                        };
                    @endphp
                    <p class="mt-2 text-sm text-gray-600">{{ $guestStatusMessage }}</p>

                    <div class="mt-5 space-y-3 border-t pt-4">
                        @foreach ($order->items as $orderItem)
                            <div class="flex items-center justify-between gap-4 border-b pb-3 last:border-b-0">
                                <div class="flex items-center gap-3">
                                    @if ($orderItem->menuItem?->image)
                                        <img src="{{ asset('storage/'.$orderItem->menuItem->image) }}" alt="{{ $orderItem->menuItem->name }}" class="h-12 w-12 rounded object-cover">
                                    @endif
                                    <div>
                                        <p class="font-medium">{{ $orderItem->menuItem?->name ?? 'Deleted menu item' }}</p>
                                        <p class="text-sm text-gray-500">Quantity: {{ $orderItem->quantity }} × GHS {{ number_format($orderItem->unit_price, 2) }}</p>
                                    </div>
                                </div>
                                <strong>GHS {{ number_format($orderItem->total_price, 2) }}</strong>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 grid gap-2 text-sm sm:grid-cols-4">
                        <span>Subtotal: <strong>GHS {{ number_format($order->subtotal, 2) }}</strong></span>
                        <span>Tax: <strong>GHS {{ number_format($order->tax, 2) }}</strong></span>
                        <span>Service: <strong>GHS {{ number_format($order->service_charge, 2) }}</strong></span>
                        <span>Total: <strong>GHS {{ number_format($order->total, 2) }}</strong></span>
                    </div>

                    @if ($order->payment_status !== 'completed')
                        <a href="{{ route('restaurant.orders.confirmation', $order) }}" class="mt-4 inline-block rounded bg-blue-600 px-4 py-2 text-white">Complete Payment</a>
                    @endif

                    @if ($order->transaction_reference)
                        <p class="mt-3 break-all text-xs text-gray-500">Reference: {{ $order->transaction_reference }}</p>
                    @endif
                </div>
            @empty
                <p class="text-gray-500">No food orders found.</p>
            @endforelse
        </div>
    </div>

</div>

</x-guest-layout>
