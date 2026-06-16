<x-guest-layout>

<div class="max-w-2xl mx-auto py-12">

    <h2 class="text-xl font-bold mb-4">
        Book Room {{ $room->room_number }}
    </h2>

    <!-- <form method="POST" action="/book">
        @csrf

        <input type="hidden" name="room_id" value="{{ $room->id }}">

        <label>Check-in</label>
        <input type="date" name="check_in" required class="w-full mb-3">

        <label>Check-out</label>
        <input type="date" name="check_out" required class="w-full mb-3">

        <button class="bg-green-600 text-white px-4 py-2 rounded">
            Proceed to Payment
        </button>

    </form> -->

    <form
        method="POST"
        action="{{ route('booking.store') }}"
        class="bg-white shadow rounded-xl p-8"
        >

        @csrf

        <input
        type="hidden"
        name="room_id"
        value="{{ $room->id }}"
        >


        <div class="mb-6">

        <label>
        Check In
        </label>

        <input
        type="date"
        name="check_in"
        required
        class="w-full border rounded p-3"
        />

        </div>


        <div class="mb-6">

        <label>
        Check Out
        </label>

        <input
        type="date"
        name="check_out"
        required
        class="w-full border rounded p-3"
        />

        </div>


        <div class="mb-6">

        Room Type:
        <strong>
        {{ $room->roomType->name }}
        </strong>

        </div>

        <button
        class="bg-blue-600 text-white px-6 py-3 rounded-xl"
        >
        Confirm Booking
        </button>

    </form>

</div>

</x-guest-layout>