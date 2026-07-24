<x-guest-layout>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-12">
        <div class="mb-8">
            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Restaurant</p>
            <h1 class="mt-2 text-3xl font-bold sm:text-4xl">Reserve a Table</h1>
            <p class="mt-3 text-gray-600">Choose an available table, date, and time for your dining experience.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($tables->isEmpty())
            <div class="rounded-xl bg-white p-6 shadow">
                <h2 class="text-xl font-bold">No tables are currently available.</h2>
                <p class="mt-2 text-gray-600">Please contact us or check back shortly.</p>
                <a href="{{ route('restaurant') }}" class="mt-5 inline-flex rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white">Back to Restaurant</a>
            </div>
        @else
            <form action="{{ route('restaurant.reserve.store') }}" method="POST" class="rounded-xl bg-white p-5 shadow sm:p-8">
                @csrf

                <div>
                    <label for="restaurant_table_id" class="font-semibold">Restaurant Table</label>
                    <select id="restaurant_table_id" name="restaurant_table_id" class="mt-2 w-full rounded-lg border-gray-300 p-3" required>
                        <option value="">Select a table</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}" @selected(old('restaurant_table_id') == $table->id)>{{ $table->table_number }} — seats {{ $table->capacity }}{{ $table->location ? ' (' . $table->location . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div><label for="guest_name" class="font-semibold">Full Name</label><input id="guest_name" type="text" name="guest_name" value="{{ old('guest_name', auth()->user()?->name) }}" class="mt-2 w-full rounded-lg border-gray-300 p-3" required></div>
                    <div><label for="guest_email" class="font-semibold">Email Address</label><input id="guest_email" type="email" name="guest_email" value="{{ old('guest_email', auth()->user()?->email) }}" class="mt-2 w-full rounded-lg border-gray-300 p-3" required></div>
                    <div><label for="guest_phone" class="font-semibold">Phone Number</label><input id="guest_phone" type="tel" name="guest_phone" value="{{ old('guest_phone', auth()->user()?->phone_number) }}" class="mt-2 w-full rounded-lg border-gray-300 p-3" required></div>
                    <div><label for="number_of_guests" class="font-semibold">Number of Guests</label><input id="number_of_guests" type="number" name="number_of_guests" min="1" value="{{ old('number_of_guests', 1) }}" class="mt-2 w-full rounded-lg border-gray-300 p-3" required></div>
                    <div><label for="reservation_date" class="font-semibold">Reservation Date</label><input id="reservation_date" type="date" name="reservation_date" min="{{ today()->toDateString() }}" value="{{ old('reservation_date') }}" class="mt-2 w-full rounded-lg border-gray-300 p-3" required></div>
                    <div><label for="reservation_time" class="font-semibold">Reservation Time</label><input id="reservation_time" type="time" name="reservation_time" value="{{ old('reservation_time') }}" class="mt-2 w-full rounded-lg border-gray-300 p-3" required></div>
                </div>

                <div class="mt-6"><label for="special_requests" class="font-semibold">Special Requests <span class="font-normal text-gray-500">(optional)</span></label><textarea id="special_requests" name="special_requests" rows="4" class="mt-2 w-full rounded-lg border-gray-300 p-3" placeholder="Accessibility needs, dietary requests, or celebrations">{{ old('special_requests') }}</textarea></div>
                <button type="submit" class="mt-8 w-full rounded-lg bg-indigo-600 px-8 py-3 font-semibold text-white hover:bg-indigo-700 sm:w-auto">Continue to Payment</button>
            </form>
        @endif
    </div>
</x-guest-layout>
