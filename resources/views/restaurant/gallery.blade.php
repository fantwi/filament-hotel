<x-guest-layout>
    <section
        class="min-h-screen bg-gray-50 py-8 sm:py-16"
        x-data="{
            selectedImage: null,
            open(image) { this.selectedImage = image; document.body.classList.add('overflow-hidden') },
            close() { this.selectedImage = null; document.body.classList.remove('overflow-hidden') }
        }"
        @keydown.escape.window="close()"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-widest text-orange-600">Gallery</p>
                <h1 class="mt-2 text-3xl font-bold text-gray-900 sm:text-5xl">Experience Our Restaurant</h1>
                <p class="mt-4 text-gray-600">Explore our dining space, atmosphere, meals, and memorable guest experiences.</p>
            </div>

            @if ($restaurant && filled($restaurant->gallery) && is_array($restaurant->gallery))
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($restaurant->gallery as $image)
                        @php($imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($image))
                        <button type="button" class="group relative aspect-[4/3] overflow-hidden rounded-2xl bg-gray-200 shadow-sm focus:outline-none focus:ring-4 focus:ring-orange-200" @click="open(@js($imageUrl))">
                            <img src="{{ $imageUrl }}" alt="{{ $restaurant->name }} gallery image {{ $loop->iteration }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 transition group-hover:opacity-100"></div>
                            <div class="absolute bottom-4 right-4 rounded-full bg-white/90 px-4 py-2 text-sm font-medium text-gray-800 opacity-0 transition group-hover:opacity-100">View Image</div>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl bg-white p-8 text-center shadow">
                    <h2 class="text-xl font-bold">Gallery images will be available soon.</h2>
                    <a href="{{ route('restaurant') }}" class="mt-5 inline-flex rounded-lg bg-orange-600 px-5 py-3 font-semibold text-white">Back to Restaurant</a>
                </div>
            @endif
        </div>

        <div x-show="selectedImage" x-cloak x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4 sm:p-8" role="dialog" aria-modal="true" @click.self="close()">
            <button type="button" class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-3xl text-white transition hover:bg-white/20" @click="close()" aria-label="Close gallery image">&times;</button>
            <img :src="selectedImage" alt="Restaurant gallery preview" class="max-h-[88vh] max-w-full rounded-xl object-contain shadow-2xl">
        </div>
    </section>
</x-guest-layout>
