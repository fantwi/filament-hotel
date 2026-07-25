<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto w-full max-w-2xl">
            <div class="mb-7 text-center sm:text-left">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Room reservation</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Book room {{ $room->room_number }}</h1>
                <p class="mt-2 text-sm leading-6 text-gray-600">Choose your stay dates to begin your reservation.</p>
            </div>

            <form method="POST" action="{{ route('booking.store') }}" class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
                @csrf
                <input type="hidden" name="room_id" value="{{ $room->id }}">

                <div class="mb-6 rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Selected room</p>
                    <p class="mt-1 text-base font-bold text-gray-900">{{ $room->roomType->name }} <span class="font-normal text-gray-600">· Room {{ $room->room_number }}</span></p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="check_in" class="block text-sm font-semibold text-gray-800">Check-in</label>
                        <input id="check_in" type="date" name="check_in" value="{{ old('check_in') }}" required class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="check_out" class="block text-sm font-semibold text-gray-800">Check-out</label>
                        <input id="check_out" type="date" name="check_out" value="{{ old('check_out') }}" required class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <button type="submit" class="mt-7 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Continue with booking</button>
            </form>
        </div>
    </section>
</x-guest-layout>
