@extends('layouts.app')

@section('content')

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-16">

    <h1 class="text-4xl font-bold mb-8">

        Reserve a Table

    </h1>

    @if ($errors->any())

        <div class="bg-red-100 text-red-700 p-4 rounded mb-6">

            <ul>

                @foreach ($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif

    <form
        action="{{ route('restaurant.reserve.store') }}"
        method="POST"
    >

        @csrf

        <div class="mb-6">

            <label class="font-semibold">

                Restaurant Table

            </label>

            <!-- @error('restaurant_table_id')

                <p class="mt-2 text-sm text-red-600">

                    {{ $message }}

                </p>

            @enderror -->

            <select
                name="restaurant_table_id"
                class="w-full border rounded-lg p-3"
            >

                @foreach($tables as $table)

                    <option value="{{ $table->id }}">

                        {{ $table->table_number }}

                        (Seats {{ $table->capacity }})

                    </option>

                @endforeach

            </select>

            @error('restaurant_table_id')

                <p class="mt-2 text-sm text-red-600">

                    {{ $message }}

                </p>

            @enderror

        </div>

        <div class="grid md:grid-cols-2 gap-6">

            <input
                type="text"
                name="guest_name"
                placeholder="Full Name"
                class="border rounded-lg p-3"
                value="{{ old('guest_name') }}"
            >

            <input
                type="email"
                name="guest_email"
                placeholder="Email Address"
                class="border rounded-lg p-3"
                value="{{ old('guest_email') }}"
            >

            <input
                type="text"
                name="guest_phone"
                placeholder="Phone Number"
                class="border rounded-lg p-3"
                value="{{ old('guest_phone') }}"
            >

            <input
                type="number"
                name="number_of_guests"
                min="1"
                class="border rounded-lg p-3"
                value="{{ old('number_of_guests') }}"
            >

        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-6">

            <input
                type="date"
                name="reservation_date"
                class="border rounded-lg p-3"
                value="{{ old('reservation_date') }}"
            >

            <input
                type="time"
                name="reservation_time"
                class="border rounded-lg p-3"
                value="{{ old('reservation_time') }}"
            >

        </div>

        <div class="mt-6">

            <textarea
                name="special_requests"
                rows="4"
                class="border rounded-lg p-3 w-full"
                placeholder="Special requests..."
            >{{ old('special_requests') }}</textarea>

        </div>

        <div class="mt-8">

            <button
                type="submit"
                class="bg-indigo-600 text-white px-8 py-3 rounded-lg"
            >

                Reserve Table

            </button>

        </div>

        </form>

        </div>

        @endsection
