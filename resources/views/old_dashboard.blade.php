<x-app-layout>
    <!-- <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot> -->

    <!-- <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div> -->

    @php
        $bookings = $bookings ?? collect();
    @endphp

    @if ($bookings->sum('balance') > 0)

        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded mb-6 flex justify-between items-center">

            <div>
                You have an outstanding balance of 
                <strong>GHS {{ number_format($bookings->sum('balance'), 2) }}</strong>.
            </div>

            <a href="/payments"
            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Complete Payment
            </a>

        </div>

    @endif

    <!-- <div class="grid grid-cols-3 gap-4 mb-6">

        <div class="bg-white p-4 rounded shadow">
            <p>Total Bookings</p>
            <h2 class="text-xl font-bold">{{ $bookings?->count() ?? 0 }}</h2>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <p>Total Spent</p>
            <h2 class="text-xl font-bold">
                GHS {{ number_format($bookings->sum('total_paid'), 2) }}
            </h2>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <p>Outstanding Balance</p>
            <h2 class="text-xl font-bold">
                GHS {{ number_format($bookings->sum('balance'), 2) }}
            </h2>
        </div>

    </div> -->

    <div class="max-w-6xl mx-auto py-6">

        <!-- STATS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <!-- Total Bookings -->
            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <p class="text-gray-500 text-sm">Total Bookings</p>
                <h2 class="text-2xl font-bold mt-2">
                    {{ $bookings->count() }}
                </h2>
            </div>

            <!-- Total Spent -->
            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <p class="text-gray-500 text-sm">Total Spent</p>
                <h2 class="text-2xl font-bold mt-2 text-green-600">
                    GHS {{ number_format($bookings->sum('total_paid'), 2) }}
                </h2>
            </div>

            <!-- Outstanding Balance -->
            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <p class="text-gray-500 text-sm">Outstanding Balance</p>
                <h2 class="text-2xl font-bold mt-2 text-red-600">
                    GHS {{ number_format($bookings->sum('balance'), 2) }}
                </h2>
            </div>

        </div>
    </div>

    <div class="max-w-7xl mx-auto py-6">

        <h2 class="text-2xl font-bold mb-4">My Bookings</h2>

        @forelse ($bookings as $booking)

            <div class="bg-white p-4 mb-4 rounded shadow">

                <div class="flex justify-between">

                    <div>
                        <p><strong>Room:</strong> {{ $booking->room->room_number ?? 'N/A' }}</p>
                        <p><strong>Check-in:</strong> {{ $booking->check_in }}</p>
                        <p><strong>Check-out:</strong> {{ $booking->check_out }}</p>
                        <p><strong>Status:</strong> {{ $booking->status }}</p>
                    </div>

                    <div>
                        <p><strong>Total:</strong> GHS {{ number_format($booking->total_price, 2) }}</p>
                        <p><strong>Paid:</strong> GHS {{ number_format($booking->total_paid, 2) }}</p>
                        <p><strong>Balance:</strong> GHS {{ number_format($booking->balance, 2) }}</p>
                    </div>

                </div>

                <!-- Payments -->
                <div class="mt-3">
                    <strong>Payments:</strong>
                    <ul>
                        @foreach ($booking->payments ?? [] as $payment)
                            <li>
                                GHS {{ $payment->amount }} ({{ $payment->method }})
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Invoice -->
                @if ($booking->invoice_number)
                    <div class="mt-3">
                        <!-- <a href="{{ asset('storage/invoices/invoice-booking-'.$booking->id.'.pdf') }}" -->
                        <a href="{{ url('/invoice/'.$booking->id) }}"
                        target="_blank"
                        class="text-blue-600 underline">
                            Download Invoice
                        </a>
                    </div>
                @endif

            </div>

        @empty
            <div class="bg-white p-8 rounded shadow text-center">

                <div class="text-5xl mb-4">🛏️</div>

                <h3 class="text-xl font-semibold mb-2">
                    No bookings yet
                </h3>

                <!-- <p class="text-gray-500 mb-4">
                    Welcome {{ auth()->user()->first_name ?? 'Guest' }} 👋 — you haven't made any bookings yet.
                </p> -->

                @php
                    $user = auth()->user();
                @endphp

                @if ($bookings->isEmpty())

                    <p class="text-gray-500 mb-4">
                        @if ($user && $user->created_at->gt(now()->subDay()))
                            Welcome {{ $user->first_name ?? 'Guest' }} 👋 — your journey starts here!
                        @else
                            You haven't made any bookings yet.
                        @endif
                    </p>

                @endif

                <p class="text-gray-500 mb-4">
                    Start your first booking and enjoy your stay with us.
                </p>

                <div class="flex justify-center gap-3 mt-4">

                    <a href="/rooms"
                        class="flex items-center gap-2 bg-blue-600 px-5 py-2.5 rounded-lg hover:bg-blue-700 transition shadow">

                        🏨 <span>Book a Room</span>

                    </a>

                    <a href="/contact"
                        class="flex items-center gap-2 bg-gray-100 px-5 py-2.5 rounded-lg hover:bg-gray-200 transition">

                        📞 <span>Contact Hotel</span>

                    </a>

                    @if ($bookings->sum('balance') > 0)
                        <a href="/payments"
                        class="flex items-center gap-2 bg-red-600 text-white px-5 py-2.5 rounded-lg">
                            💳 <span>Complete Payment</span>
                        </a>
                    @endif

                </div>

                <!-- <a href="/rooms"
                class="inline-block bg-blue-600 text-gray px-4 py-2 rounded hover:bg-blue-700">
                    Browse Rooms
                </a> -->

            </div>
        @endforelse

    </div>

</x-app-layout>
