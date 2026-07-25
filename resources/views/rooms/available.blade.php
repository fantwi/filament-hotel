<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8 max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Available rooms</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $type->name }} rooms</h1>
                <p class="mt-3 text-sm leading-6 text-gray-600 sm:text-base">Choose an available room and continue to reserve your stay.</p>
                @if ($rooms->count() < 3 && $rooms->isNotEmpty())
                    <p class="mt-3 inline-flex rounded-full bg-yellow-50 px-3 py-1 text-sm font-semibold text-yellow-800">Only a few rooms left</p>
                @endif
            </div>

            @if ($rooms->isEmpty())
                <div class="rounded-2xl border border-red-100 bg-white p-6 text-center shadow-sm sm:p-10">
                    <h2 class="text-xl font-bold text-gray-900">No rooms available right now</h2>
                    <p class="mt-2 text-sm text-gray-600">Please try another room type or check back later.</p>
                    <a href="{{ route('rooms.index') }}" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">View room types</a>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($rooms as $room)
                        <article class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Room</p><h2 class="mt-1 text-2xl font-bold text-gray-900">{{ $room->room_number }}</h2></div>
                                <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">Available</span>
                            </div>
                            <div class="mt-8 border-t border-gray-100 pt-5"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nightly rate</p><p class="mt-1 text-xl font-bold text-blue-700">GHS {{ number_format($type->price_per_night ?? 0, 2) }} <span class="text-sm font-normal text-gray-500">/ night</span></p></div>
                            <a href="{{ route('booking.select', $room->id) }}" class="mt-6 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Reserve room</a>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-guest-layout>
