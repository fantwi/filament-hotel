<x-guest-layout>

<div class="mx-auto max-w-xl px-4 py-8 sm:px-6 sm:py-12">

    <h1
        class="text-xl font-bold mb-8"
    >

        Book
        {{ $room->name }}

    </h1>

    <form
        method="POST"
        action="{{
            route(
                'conference.booking.store'
            )
        }}"
        class="rounded-xl bg-white p-5 shadow sm:p-8"
    >

        @csrf

        <input
            type="hidden"
            name="conference_room_id"
            value="{{ $room->id }}"
        >

        <div class="mb-5">

            <label class="font-semibold">

                Booking Date

            </label>

            <input
                type="date"
                name="booking_date"
                required
                class="w-full border rounded-lg p-3 mt-2"
            >

        </div>

        <div class="grid md:grid-cols-2 gap-5">

            <div>

                <label class="font-semibold">

                    Start Time

                </label>

                <input
                    type="time"
                    name="start_time"
                    required
                    class="w-full border rounded-lg p-3 mt-2"
                >

            </div>

            <div>

                <label class="font-semibold">

                    End Time

                </label>

                <input
                    type="time"
                    name="end_time"
                    required
                    class="w-full border rounded-lg p-3 mt-2"
                >

            </div>

        </div>

        <div class="mt-5">

            <label class="font-semibold">

                Number of Attendees

            </label>

            <input
                type="number"
                name="attendees"
                min="1"
                max="{{ $room->capacity }}"
                required
                class="w-full border rounded-lg p-3 mt-2"
            >

        </div>

        <div class="mt-5">

            <label class="font-semibold">

                Special Requests

            </label>

            <textarea
                name="special_requests"
                rows="4"
                class="w-full border rounded-lg p-3 mt-2"
            ></textarea>

        </div>

        <button
            class="bg-blue-600
            text-white
            px-6 py-3
            rounded-lg mt-6 w-full"
        >

            Confirm Booking

        </button>

    </form>

</div>

</x-guest-layout>
