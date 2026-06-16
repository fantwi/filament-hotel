<x-guest-layout>

@php

    $pricePerNight =
        session('booking.room_price') ?? 0;

    $checkIn =
        old('check_in');

    $checkOut =
        old('check_out');

    $nights = 1;

    if ($checkIn && $checkOut) {

        $nights = \Carbon\Carbon::parse($checkIn)
            ->diffInDays(
                \Carbon\Carbon::parse($checkOut)
            );

        $nights = max($nights, 1);
    }

    $estimatedTotal =
        $pricePerNight * $nights;

@endphp

<!-- <div class="min-h-screen bg-gray-50 py-12 px-4"> -->
<!-- // TODO: Add booking calculator -->
<div
    x-data="bookingCalculator()"
    x-ref="booking"
    class="min-h-screen bg-gray-50 py-12 px-4"
>

    <div class="max-w-6xl mx-auto grid lg:grid-cols-3 gap-10">

        <!-- LEFT SIDE -->
        <div class="lg:col-span-2">

            <!-- HEADER -->
            <div class="mb-8">

                <p class="text-blue-600 font-semibold mb-2">
                    Hotel Reservation
                </p>

                <h1 class="text-4xl font-bold text-gray-900">
                    Complete Your Booking
                </h1>

                <p class="text-gray-500 mt-3">
                    Select your stay dates and guest information.
                </p>

            </div>

            @if ($errors->has('dates'))

            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6">

                {{ $errors->first('dates') }}

            </div>

            @endif

            <!-- FORM CARD -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">

                <form method="POST" action="/booking/details">

                    @csrf

                    <!-- DATES -->
                    <div class="grid md:grid-cols-2 gap-6 mb-6">

                        <!-- CHECK IN -->
                        <div>

                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Check In
                            </label>

                            <input
                                type="text"
                                id="check_in"
                                name="check_in"
                                x-model="checkIn"
                                value="{{ old('check_in') }}"
                                required
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                            >

                        </div>

                        <!-- CHECK OUT -->
                        <div>

                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Check Out
                            </label>

                            <input
                                type="text"
                                id="check_out"
                                name="check_out"
                                x-model="checkOut"
                                value="{{ old('check_out') }}"
                                required
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                            >

                        </div>

                    </div>

                    <!-- GUESTS -->
                    <div class="mb-8">

                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Guests
                        </label>

                        <input
                            type="number"
                            name="guests"
                            value="{{ old('guests', 1) }}"
                            min="1"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                        >

                    </div>

                    <!-- <p class="text-red-500">

                    Check-in:
                    <span x-text="checkIn"></span>

                    </p>

                    <p class="text-red-500">

                    Check-out:
                    <span x-text="checkOut"></span>

                    </p> -->

                    <!-- BUTTON -->
                    <button
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl transition shadow-sm"
                    >

                        Continue to Payment

                    </button>

                </form>

            </div>

        </div>


        <!-- RIGHT SIDE SUMMARY --> 
        <div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-10">

                <h3 class="text-xl font-bold mb-6">
                    Booking Summary
                </h3>

                <!-- ROOM -->
                <div class="flex justify-between mb-4">

                    <span class="text-gray-500">
                        Room Type
                    </span>

                    <span class="font-semibold">
                        {{ session('booking.room_name') ?? 'Selected Room Type' }}
                    </span>

                </div>

                <div class="flex justify-between mb-4">

                    <span class="text-gray-500">
                        Room Number
                    </span>

                    <span class="font-semibold">
                        {{ session('booking.room_number') ?? 'Selected Room' }}
                    </span>

                </div>

                <!-- PRICE -->
                <div class="flex justify-between mb-4">

                    <span class="text-gray-500">
                        Price / Night
                    </span>

                    <span class="font-semibold text-blue-600">
                        GHS {{ number_format($pricePerNight, 2) }}
                    </span>

                </div>

                <!-- PRICE -->
                <div class="flex justify-between mb-4">

                    <span class="text-gray-500">
                        <!-- Number of Nights -->
                        Stay Duration (nights)
                    </span>

                    <span class="font-semibold text-blue-600">
                        <!-- {{ $nights }} -->
                        <span x-text="nights"></span>
                    </span>

                </div>

                <!-- SERVICE -->
                <div class="flex justify-between mb-4">

                    <span class="text-gray-500">
                        Service Fee
                    </span>

                    <span>
                        GHS 0.00
                    </span>

                </div>

                <hr class="my-5">

                <!-- TOTAL -->
                <div class="flex justify-between items-center">

                    <span class="text-lg font-bold">
                        Estimated Total
                    </span>

                    <span class="text-2xl font-bold text-blue-600">
                        GHS <span x-text="formattedTotal"></span>
                        <!-- {{ number_format($estimatedTotal, 2) }} -->
                    </span>

                </div>

                <!-- TRUST -->
                <div class="mt-8 space-y-3 text-sm text-gray-500">

                    <div class="flex items-center gap-2">
                        ✅ Free cancellation within 24 hours
                    </div>

                    <div class="flex items-center gap-2">
                        🔒 Secure payment processing
                    </div>

                    <div class="flex items-center gap-2">
                        🏨 Instant reservation confirmation
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

function bookingCalculator() {

    return {

        checkIn: '{{ old('check_in') ?? '' }}',
        checkOut: '{{ old('check_out') ?? '' }}',

        pricePerNight:
            {{ session('booking.room_price') ?? 0 }},

        get nights() {

            if (!this.checkIn || !this.checkOut) {
                return 1;
            }

            let start =
                new Date(this.checkIn);

            let end =
                new Date(this.checkOut);

            let diff =
                (end - start) /
                (1000 * 60 * 60 * 24);

            return diff > 0 ? diff : 1;
        },

        get total() {

            return this.nights *
                   this.pricePerNight;

        },

        get formattedTotal() {

            return new Intl.NumberFormat()
                .format(this.total);

        }

    }

}

</script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    let disabledDates =
        @json($disabledDates ?? []);

    let checkoutPicker;

    flatpickr("#check_in", {

        minDate: "today",

        disable: disabledDates,

        dateFormat: "Y-m-d",

        onChange: function(selectedDates, dateStr) {

            if (checkoutPicker) {
                checkoutPicker.set(
                    'minDate',
                    dateStr
                );
            }
            
            // update Alpine model
            //document.querySelector(
            //    '[x-data]'
            //).__x.$data.checkIn = dateStr;

            Alpine.evaluate(
                document.querySelector('[x-data]'),
                `checkIn='${dateStr}'`
            );

        }

    });

    checkoutPicker = flatpickr("#check_out", {

        minDate: "today",

        disable: disabledDates,

        dateFormat: "Y-m-d",

        onChange: function(selectedDates, dateStr) {

            // update Alpine model
            //document.querySelector(
            //    '[x-data]'
            //).__x.$data.checkOut = dateStr;

            Alpine.evaluate(
                document.querySelector('[x-data]'),
                `checkOut='${dateStr}'`
            );

        }

    });

});

</script>

</x-guest-layout>