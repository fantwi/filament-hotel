<x-guest-layout>
    <section x-data="{ images: [], active: 0, open(images) { this.images = images; this.active = 0; document.body.classList.add('overflow-hidden') }, close() { this.images = []; document.body.classList.remove('overflow-hidden') }, previous() { this.active = (this.active + this.images.length - 1) % this.images.length }, next() { this.active = (this.active + 1) % this.images.length } }" @keydown.escape.window="close()" class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8 max-w-2xl"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Stay with us</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Find your room</h1><p class="mt-3 text-sm leading-6 text-gray-600 sm:text-base">Explore comfortable room types designed for every kind of stay.</p></div>
            <div class="grid gap-6 md:grid-cols-2">
                @forelse ($roomTypes as $roomType)
                    <article class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5">
                        @if ($roomType->image)
                            <img src="{{ asset('storage/' . $roomType->image) }}" alt="{{ $roomType->name }}" class="h-52 w-full object-cover sm:h-60">
                        @else
                            <div class="flex h-52 items-center justify-center bg-slate-100 text-sm font-medium text-slate-500 sm:h-60">Room image</div>
                        @endif
                        <div class="p-5 sm:p-6"><h2 class="text-xl font-bold text-gray-900">{{ $roomType->name }}</h2><p class="mt-2 line-clamp-3 text-sm leading-6 text-gray-600">{{ $roomType->description }}</p>
                            <div class="mt-4 flex flex-wrap gap-2">@foreach ($roomType->facilities as $facility)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $facility->name }}</span>@endforeach</div>
                            <div class="mt-5 border-t border-gray-100 pt-5"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">From</p><p class="mt-1 text-lg font-bold text-blue-700">GHS {{ number_format($roomType->price_per_night ?? 0, 2) }} <span class="text-sm font-normal text-gray-500">/ night</span></p></div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-3"><a href="{{ route('rooms.available', $roomType->id) }}" class="flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">View rooms</a><a href="{{ route('rooms.calendar', $roomType->id) }}" class="flex min-h-11 items-center justify-center rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-200">Availability</a>@if (filled($roomType->gallery))<button type="button" @click="open(@js(collect($roomType->gallery)->map(fn ($image) => asset('storage/' . $image))->values()))" class="flex min-h-11 items-center justify-center rounded-xl bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">Gallery</button>@endif</div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl bg-white p-8 text-center text-gray-600 shadow-sm md:col-span-2">No room types are available at the moment.</div>
                @endforelse
            </div>
        </div>

        <div x-show="images.length" x-cloak x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4 sm:p-8" role="dialog" aria-modal="true" @click.self="close()">
            <button type="button" @click="close()" class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-3xl text-white hover:bg-white/20" aria-label="Close gallery">&times;</button>
            <button type="button" x-show="images.length > 1" @click="previous()" class="absolute left-3 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20 sm:left-8" aria-label="Previous image">&lsaquo;</button>
            <img :src="images[active]" alt="Room gallery image" class="max-h-[78vh] max-w-full rounded-xl object-contain shadow-2xl">
            <button type="button" x-show="images.length > 1" @click="next()" class="absolute right-3 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20 sm:right-8" aria-label="Next image">&rsaquo;</button>
            <div class="absolute bottom-5 flex max-w-[90vw] gap-2 overflow-x-auto"><template x-for="(image, index) in images" :key="image"><button type="button" @click="active = index" class="h-14 w-16 shrink-0 overflow-hidden rounded-lg ring-2" :class="active === index ? 'ring-white' : 'ring-transparent'"><img :src="image" alt="" class="h-full w-full object-cover"></button></template></div>
        </div>
    </section>
</x-guest-layout>
