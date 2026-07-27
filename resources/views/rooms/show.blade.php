<x-guest-layout>
    <section class="bg-slate-50 px-4 py-8 transition-colors dark:bg-[#161b48] sm:px-6 sm:py-14 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-8 lg:grid-cols-5 lg:items-start">
                <div class="lg:col-span-3">
                    @if ($type->image)
                        <img src="{{ asset('storage/' . $type->image) }}" alt="{{ $type->name }}" class="h-52 w-full rounded-2xl object-cover shadow-xl shadow-slate-200/70 sm:h-96">
                    @else
                        <div class="flex h-52 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300 sm:h-96">Room image</div>
                    @endif
                    <div class="mt-7"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Room type</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ $type->name }}</h1><p class="mt-4 text-sm leading-7 text-gray-600 dark:text-slate-300 sm:text-base">{{ $type->description }}</p></div>
                    <div class="mt-8"><h2 class="text-xl font-bold text-gray-900 dark:text-white">Room facilities</h2><div class="mt-4 grid gap-3 sm:grid-cols-2">@forelse ($type->facilities as $facility)<div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-900 dark:text-slate-200 dark:ring-white/10"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-green-50 font-bold text-green-700">✓</span>{{ $facility->name }}</div>@empty<p class="text-sm text-slate-500 dark:text-slate-400">Facilities will be listed soon.</p>@endforelse</div></div>
                </div>
                <aside class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 dark:bg-slate-900 dark:shadow-none dark:ring-white/10 sm:p-6 lg:sticky lg:top-24 lg:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">From</p><p class="mt-1 text-2xl font-bold text-blue-700">GHS {{ number_format($type->price_per_night ?? 0, 2) }} <span class="text-sm font-normal text-gray-500">/ night</span></p>
                    <div class="mt-5 border-t border-gray-100 pt-5"><p class="text-sm text-gray-600">Available rooms</p><p class="mt-1 text-xl font-bold text-green-700">{{ $availableRooms }}</p></div>
                    <a href="{{ route('rooms.available', $type->id) }}" class="mt-6 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">View available rooms</a>
                    <a href="{{ route('rooms.calendar', $type->id) }}" class="mt-3 flex min-h-11 w-full items-center justify-center rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Check availability calendar</a>
                </aside>
            </div>
        </div>
    </section>
</x-guest-layout>
