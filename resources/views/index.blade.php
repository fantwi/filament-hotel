<x-guest-layout>
    <section class="relative isolate overflow-hidden bg-[#161b48e6] py-20 text-white sm:py-28">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,_rgba(245,158,11,0.35),_transparent_42%),linear-gradient(135deg,_#0f172a,_#1e293b)]"></div>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-300">Welcome to My Hotel</p>
                <h1 class="mt-5 text-4xl font-bold tracking-tight sm:text-6xl">Stay comfortably. Dine memorably. Gather beautifully.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">Discover welcoming rooms, flexible conference spaces, and a restaurant made for memorable dining experiences.</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="/rooms" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-amber-400 px-6 py-3 font-semibold text-slate-950 transition hover:bg-amber-300">Explore rooms</a>
                    <a href="{{ route('restaurant') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/30 bg-white/10 px-6 py-3 font-semibold text-white transition hover:bg-white/20">Visit the restaurant</a>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div><p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Stay</p><h2 class="mt-2 text-3xl font-bold text-gray-900">Rooms for every visit</h2></div>
            <a href="/rooms" class="font-semibold text-indigo-600 hover:text-indigo-800">View all rooms →</a>
        </div>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($roomTypes as $roomType)
                <article class="overflow-hidden rounded-xl bg-white shadow">
                    @if ($roomType->image)<img src="{{ asset('storage/'.$roomType->image) }}" alt="{{ $roomType->name }}" class="h-48 w-full object-cover" loading="lazy">@endif
                    <div class="p-6"><h3 class="text-xl font-bold">{{ $roomType->name }}</h3><p class="mt-2 line-clamp-2 text-gray-600">{{ $roomType->description }}</p><p class="mt-5 font-semibold text-indigo-700">From GHS {{ number_format($roomType->price_per_night, 2) }} / night</p></div>
                </article>
            @empty
                <p class="text-gray-500">Room information will be available soon.</p>
            @endforelse
        </div>
    </section>

    <section class="bg-gray-50 py-10 sm:py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div class="rounded-2xl bg-indigo-950 p-7 text-white sm:p-10"><p class="text-sm font-semibold uppercase tracking-widest text-amber-300">Dine</p><h2 class="mt-3 text-3xl font-bold">{{ $restaurant?->name ?? 'Our Restaurant' }}</h2><p class="mt-4 leading-7 text-indigo-100">{{ $restaurant?->description ?? 'Fresh flavours and warm hospitality for every occasion.' }}</p><a href="{{ route('restaurant.menu') }}" class="mt-7 inline-flex rounded-lg bg-amber-400 px-5 py-3 font-semibold text-slate-950">Explore the Menu</a></div>
            <div class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-gray-950/5 sm:p-10"><p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Meet</p><h2 class="mt-3 text-3xl font-bold text-gray-900">Conference spaces</h2><p class="mt-4 leading-7 text-gray-600">Professional, flexible venues for meetings, workshops, and celebrations.</p><a href="/conference-rooms" class="mt-7 inline-flex rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white">View Conference Rooms</a><p class="mt-5 text-sm text-gray-500">{{ $conferenceRooms->count() }} space(s) currently available.</p></div>
        </div>
    </section>
</x-guest-layout>
