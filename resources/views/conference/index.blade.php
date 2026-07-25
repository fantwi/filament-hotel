<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8 max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Meetings and events</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Conference rooms</h1>
                <p class="mt-3 text-sm leading-6 text-gray-600 sm:text-base">Find a flexible space for your next meeting, workshop, or private event.</p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($rooms as $room)
                    <article class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5">
                        @if ($room->image)
                            <img src="{{ asset('storage/' . $room->image) }}" alt="{{ $room->name }}" class="h-52 w-full object-cover sm:h-56">
                        @else
                            <div class="flex h-52 items-center justify-center bg-slate-100 text-sm font-medium text-slate-500 sm:h-56">Conference room</div>
                        @endif
                        <div class="p-5 sm:p-6">
                            <h2 class="text-xl font-bold text-gray-900">{{ $room->name }}</h2>
                            <p class="mt-2 line-clamp-3 text-sm leading-6 text-gray-600">{{ $room->description }}</p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Up to {{ $room->capacity }} guests</span>
                                @foreach ($room->facilities as $facility)
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $facility->name }}</span>
                                @endforeach
                            </div>

                            <div class="mt-5 flex items-end justify-between gap-4 border-t border-gray-100 pt-5">
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">From</p><p class="mt-1 text-lg font-bold text-gray-900">GHS {{ number_format($room->price_per_hour, 2) }}<span class="text-sm font-normal text-gray-500"> / hour</span></p></div>
                                <a href="{{ route('conference.book', $room->id) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Book room</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl bg-white p-8 text-center text-gray-600 shadow-sm sm:col-span-2 xl:col-span-3">There are no conference rooms available at the moment.</div>
                @endforelse
            </div>
        </div>
    </section>
</x-guest-layout>
