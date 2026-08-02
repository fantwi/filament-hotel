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
    $vatRate = config('billing.vat_rate');
    $nhilRate = config('billing.nhil_rate');
    $serviceRate = config('billing.service_charge_rate');

@endphp

<!-- <div class="min-h-screen bg-gray-50 py-12 px-4"> -->
<!-- // TODO: Add booking calculator -->
<div
    x-data="bookingCalculator()"
    x-ref="booking"
    class="px-4 py-10 sm:px-6 sm:py-14"
>

    <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-3 lg:gap-10">

        <!-- LEFT SIDE -->
        <div class="lg:col-span-2">

            <!-- HEADER -->
            <div class="mb-8">

                <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">
                    Hotel Reservation
                </p>

                <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-4xl">
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
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xl shadow-slate-200/70 sm:p-8">

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
                                class="min-h-12 w-full rounded-xl border border-gray-200 px-4 py-3 text-base focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition"
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
                                class="min-h-12 w-full rounded-xl border border-gray-200 px-4 py-3 text-base focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition"
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
                            class="min-h-12 w-full rounded-xl border border-gray-200 px-4 py-3 text-base focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition"
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

                    <div class="mb-6">
                        <label class="mb-2 block text-sm font-semibold text-gray-700">Promotion code</label>
                        <input type="text" name="promotion_code" value="{{ old('promotion_code') }}" class="min-h-12 w-full rounded-xl border border-gray-200 px-4 py-3 text-base" placeholder="Optional promotion code">
                        @error('promotion_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- BUTTON -->
                    <button
                        type="submit"
                        class="flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >

                        Continue to Payment

                    </button>

                </form>

            </div>

        </div>


        <!-- RIGHT SIDE SUMMARY --> 
        <div>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm lg:sticky lg:top-10 sm:p-6">

                <h3 class="text-xl font-bold mb-6">
                    Booking Summary
                </h3>

                <!-- ROOM -->
                <div class="flex gap-4 justify-between mb-4">

                    <span class="text-gray-500">
                        Room Type
                    </span>

                    <span class="font-semibold">
                        {{ session('booking.room_name') ?? 'Selected Room Type' }}
                    </span>

                </div>

                <div class="flex gap-4 justify-between mb-4">

                    <span class="text-gray-500">
                        Room Number
                    </span>

                    <span class="font-semibold">
                        {{ session('booking.room_number') ?? 'Selected Room' }}
                    </span>

                </div>

                <!-- PRICE -->
                <div class="flex gap-4 justify-between mb-4">

                    <span class="text-gray-500">
                        Price / Night
                    </span>

                    <span class="font-semibold text-blue-600">
                        GHS {{ number_format($pricePerNight, 2) }}
                    </span>

                </div>

                <!-- PRICE -->
                <div class="flex gap-4 justify-between mb-4">

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
                <div class="flex gap-4 justify-between mb-4">

                    <span class="text-gray-500">
                        Service Fee
                    </span>

                    <span>
                        GHS <span x-text="serviceCharge.toFixed(2)"></span>
                    </span>

                </div>

                <div class="mb-3 flex justify-between"><span class="text-gray-500">VAT ({{ $vatRate }}%)</span><span>GHS <span x-text="vat.toFixed(2)"></span></span></div>
                <div class="mb-3 flex justify-between"><span class="text-gray-500">NHIL ({{ $nhilRate }}%)</span><span>GHS <span x-text="nhil.toFixed(2)"></span></span></div>
                <hr class="my-5">

                <!-- TOTAL -->
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

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

        get subtotal() { return this.nights * this.pricePerNight; },
        get vat() { return this.subtotal * {{ $vatRate }} / 100; },
        get nhil() { return this.subtotal * {{ $nhilRate }} / 100; },
        get serviceCharge() { return this.subtotal * {{ $serviceRate }} / 100; },
        get total() { return this.subtotal + this.vat + this.nhil + this.serviceCharge; },

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
