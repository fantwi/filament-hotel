<x-guest-layout>
    @php
        $roomTypes = $roomTypes ?? collect();
        $conferenceRooms = $conferenceRooms ?? collect();
    @endphp

    <main class="overflow-hidden bg-slate-50 text-slate-900 transition-colors dark:bg-[#161b48] dark:text-slate-100">
        <section class="relative isolate overflow-hidden bg-[#161b48] py-12 text-white sm:py-24 lg:py-32">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_82%_18%,rgba(56,189,248,0.28),transparent_24%),radial-gradient(circle_at_12%_90%,rgba(251,191,36,0.22),transparent_28%),linear-gradient(135deg,#0b102e,#161b48_52%,#1d4ed8)]"></div>
            <div class="absolute inset-x-0 bottom-0 h-40 -z-10 bg-gradient-to-t from-[#161b48] to-transparent"></div>
            <div class="mx-auto grid max-w-7xl items-center gap-8 px-4 sm:gap-12 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8">
                <div>
                    <p class="inline-flex rounded-full border border-cyan-200/25 bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-cyan-100 backdrop-blur">My Hotel</p>
                    <h1 class="mt-5 max-w-3xl text-3xl font-bold tracking-tight sm:mt-6 sm:text-5xl lg:text-6xl">Every stay should feel like it was made for you.</h1>
                    <p class="mt-6 max-w-2xl text-base leading-7 text-indigo-100 sm:text-lg sm:leading-8">Rest in comfort, gather with confidence, and enjoy memorable meals — all with the warm service of My Hotel.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('rooms.index') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-amber-400 px-6 py-3 font-semibold text-slate-950 shadow-lg shadow-amber-950/20 transition hover:bg-amber-300 sm:w-auto">Find a room</a>
                        <a href="{{ route('restaurant') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-white/30 bg-white/10 px-6 py-3 font-semibold text-white backdrop-blur transition hover:bg-white/20 sm:w-auto">Explore dining</a>
                    </div>
                    <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-white/15 pt-6 text-sm"><div><dt class="text-indigo-200">Rooms</dt><dd class="mt-1 text-xl font-bold">Comfort</dd></div><div><dt class="text-indigo-200">Events</dt><dd class="mt-1 text-xl font-bold">Flexible</dd></div><div><dt class="text-indigo-200">Dining</dt><dd class="mt-1 text-xl font-bold">Fresh</dd></div></dl>
                </div>
                <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                    <div class="absolute -inset-3 rounded-[2rem] bg-gradient-to-br from-cyan-300/25 to-amber-300/20 blur-2xl"></div>
                    <div class="relative grid gap-3 rounded-[2rem] border border-white/20 bg-white/10 p-4 shadow-2xl backdrop-blur sm:grid-cols-2 sm:gap-4 sm:p-6">
                        <div class="rounded-2xl bg-white p-5 text-slate-900 shadow-lg"><p class="text-sm font-semibold text-indigo-600">Stay</p><p class="mt-2 text-xl font-bold">Rest easy</p><p class="mt-2 text-sm leading-6 text-slate-500">Thoughtful rooms for business, family, and leisure stays.</p></div>
                        <div class="rounded-2xl bg-amber-400 p-5 text-slate-950 shadow-lg"><p class="text-sm font-semibold">Dine</p><p class="mt-2 text-xl font-bold">Taste more</p><p class="mt-2 text-sm leading-6 text-amber-950/80">Reserve a table or order the meals you love.</p></div>
                        <div class="rounded-2xl bg-cyan-300 p-5 text-slate-950 shadow-lg sm:col-span-2"><p class="text-sm font-semibold">Meet</p><p class="mt-1 text-xl font-bold">Bring people together</p><p class="mt-1 text-sm text-cyan-950/80">Flexible spaces for work, celebrations, and everything between.</p></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-20 lg:px-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-300">Stay your way</p><h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Rooms for every kind of visit</h2><p class="mt-3 max-w-2xl text-slate-600 dark:text-slate-300">Choose a room that gives you the space, comfort, and quiet you need.</p></div><a href="{{ route('rooms.index') }}" class="inline-flex min-h-11 items-center font-semibold text-indigo-700 transition hover:text-indigo-900 dark:text-indigo-300 dark:hover:text-indigo-100">View all rooms <span class="ml-2">→</span></a></div>

            <div class="mt-9 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($roomTypes->take(3) as $roomType)
                    <article class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl dark:bg-slate-900 dark:ring-white/10">
                        <div class="relative h-44 overflow-hidden bg-slate-200 dark:bg-slate-800 sm:h-52">
                            @if ($roomType->image)<img src="{{ asset('storage/' . $roomType->image) }}" alt="{{ $roomType->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">@else<div class="flex h-full items-center justify-center bg-gradient-to-br from-indigo-100 to-cyan-100 text-sm font-semibold text-indigo-700 dark:from-indigo-950 dark:to-slate-800 dark:text-indigo-200">My Hotel room</div>@endif
                            <span class="absolute bottom-3 left-3 rounded-full bg-slate-950/75 px-3 py-1 text-xs font-bold text-white backdrop-blur">From GHS {{ number_format($roomType->price_per_night, 2) }}</span>
                        </div>
                        <div class="p-4 sm:p-5"><h3 class="text-xl font-bold">{{ $roomType->name }}</h3><p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $roomType->description }}</p><a href="{{ url('/rooms/' . $roomType->id) }}" class="mt-5 inline-flex min-h-11 items-center text-sm font-semibold text-indigo-700 dark:text-indigo-300">Explore this room →</a></div>
                    </article>
                @empty
                    <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-white/10 sm:col-span-2 lg:col-span-3"><p class="font-semibold">Room information is on its way.</p><a href="{{ route('rooms.index') }}" class="mt-3 inline-block text-sm font-semibold text-indigo-700 dark:text-indigo-300">Browse rooms →</a></div>
                @endforelse
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white py-12 dark:border-white/10 dark:bg-slate-900/50 sm:py-20">
            <div class="mx-auto grid max-w-7xl gap-5 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <article class="relative overflow-hidden rounded-3xl bg-[#161b48] p-6 text-white shadow-xl shadow-indigo-950/20 sm:p-10"><div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-cyan-300/15 blur-3xl"></div><div class="relative"><p class="text-sm font-bold uppercase tracking-[0.18em] text-amber-300">Dine with us</p><h2 class="mt-3 text-2xl font-bold sm:text-3xl">{{ $restaurant?->name ?? 'A meal worth lingering over' }}</h2><p class="mt-4 max-w-lg leading-7 text-indigo-100">{{ $restaurant?->description ?? 'Fresh flavours, thoughtful service, and a welcoming table for every occasion.' }}</p><div class="mt-7 flex flex-col gap-3 sm:flex-row"><a href="{{ route('restaurant.menu') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-amber-400 px-4 py-2 font-semibold text-slate-950 transition hover:bg-amber-300 sm:w-auto">View the menu</a><a href="{{ route('restaurant.reserve') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-white/25 px-4 py-2 font-semibold text-white transition hover:bg-white/10 sm:w-auto">Reserve a table</a></div></div></article>
                <article class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-cyan-100 to-indigo-100 p-6 text-slate-900 shadow-sm ring-1 ring-slate-200/70 dark:from-slate-800 dark:to-indigo-950 dark:text-white dark:ring-white/10 sm:p-10"><div class="absolute -bottom-16 -right-10 h-56 w-56 rounded-full bg-indigo-500/15 blur-3xl"></div><div class="relative"><p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-700 dark:text-indigo-300">Meet & celebrate</p><h2 class="mt-3 text-2xl font-bold sm:text-3xl">Spaces with room for ideas</h2><p class="mt-4 max-w-lg leading-7 text-slate-600 dark:text-slate-300">Professional, flexible venues for meetings, workshops, and celebrations of every size.</p><div class="mt-7 flex flex-col items-start gap-4 sm:flex-row sm:items-center"><a href="{{ url('/conference-rooms') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 font-semibold text-white transition hover:bg-indigo-700 sm:w-auto">Explore venues</a><span class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $conferenceRooms->count() }} space(s) available</span></div></div></article>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-20 lg:px-8"><div class="rounded-3xl border border-indigo-200 bg-indigo-50 p-6 text-center dark:border-indigo-400/20 dark:bg-indigo-400/10 sm:p-10"><p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-300">Plan with confidence</p><h2 class="mt-3 text-2xl font-bold sm:text-3xl">Your next visit starts here.</h2><p class="mx-auto mt-3 max-w-2xl text-slate-600 dark:text-slate-300">Choose your room, book your event space, or reserve a table in just a few steps.</p><div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row"><a href="{{ route('rooms.index') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700 sm:w-auto">Start planning</a><a href="{{ route('contact') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-indigo-300 px-5 py-3 font-semibold text-indigo-800 hover:bg-white dark:border-indigo-300/40 dark:text-indigo-200 dark:hover:bg-white/10 sm:w-auto">Contact us</a></div></div></section>
    </main>
</x-guest-layout>
