<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto w-full max-w-2xl">
            <div class="mb-7 text-center sm:text-left">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Conference reservation</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Book {{ $room->name }}</h1>
                <p class="mt-2 text-sm leading-6 text-gray-600">Plan your event in a space for up to {{ $room->capacity }} attendees.</p>
            </div>

            <form method="POST" action="{{ route('conference.booking.store') }}" class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
                @csrf
                <input type="hidden" name="conference_room_id" value="{{ $room->id }}">

                <div class="mb-6 rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Hourly rate</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">GHS {{ number_format($room->price_per_hour, 2) }} <span class="text-sm font-normal text-gray-600">per hour</span></p>
                </div>

                <div class="space-y-5">
                    <div>
                        <label for="booking_date" class="block text-sm font-semibold text-gray-800">Event date</label>
                        <input id="booking_date" type="date" name="booking_date" value="{{ old('booking_date') }}" required class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="start_time" class="block text-sm font-semibold text-gray-800">Start time</label>
                            <input id="start_time" type="time" name="start_time" value="{{ old('start_time') }}" required class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="end_time" class="block text-sm font-semibold text-gray-800">End time</label>
                            <input id="end_time" type="time" name="end_time" value="{{ old('end_time') }}" required class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label for="attendees" class="block text-sm font-semibold text-gray-800">Number of attendees</label>
                        <input id="attendees" type="number" name="attendees" value="{{ old('attendees', 1) }}" min="1" max="{{ $room->capacity }}" required class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="special_requests" class="block text-sm font-semibold text-gray-800">Special requests <span class="font-normal text-gray-500">(optional)</span></label>
                        <textarea id="special_requests" name="special_requests" rows="4" class="mt-2 block w-full rounded-xl border-gray-300 px-4 py-3 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('special_requests') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="mt-7 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Continue to payment</button>
            </form>
        </div>
    </section>
</x-guest-layout>
