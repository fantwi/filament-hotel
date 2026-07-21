<x-guest-layout>

@if(session('success'))

    <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700">

        {{ session('success') }}

    </div>

@endif

<section class="relative">

    <img
        src="{{ asset('storage/' . $restaurant->hero_image) }}"
        class="w-full h-[500px] object-cover"
        alt="{{ $restaurant->name }}"
    >

    <div
        class="absolute inset-0
        bg-black/50
        flex flex-col
        justify-center
        items-center
        text-white"
    >

        <h1 class="text-5xl font-bold mb-4">
            {{ $restaurant->name }}
        </h1>

        <p class="text-xl">
            {{ $restaurant->description }} • Local & International Cuisine
        </p>

        <p>
        <a
            href="{{ route('restaurant.reserve') }}"
            class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
        >
            Reserve a Table
        </a>
        </p>

    </div>

    <!-- <a
        href="{{ route('restaurant.reserve') }}"
        class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
    >
        Reserve a Table
    </a> -->

</section>

<section class="max-w-7xl mx-auto py-16">

    <div class="grid md:grid-cols-2 gap-12">

        <div>

            <h2 class="text-3xl font-bold mb-6">
                About Our Restaurant
            </h2>

            <p class="text-gray-600 leading-8">

                Experience world-class dining prepared
                by our professional chefs.

                We serve local Ghanaian dishes,
                continental cuisine,
                desserts,
                cocktails
                and beverages.

            </p>

        </div>

        <div class="space-y-4">

            <div>
                <strong>Opening:</strong>
                {{ \Carbon\Carbon::parse($restaurant->opening_time)->format('g:i A') }}
            </div>

            <div>
                <strong>Closing:</strong>
                {{ \Carbon\Carbon::parse($restaurant->closing_time)->format('g:i A') }}
            </div>

            <div>
                <strong>Capacity:</strong>
                {{ $restaurant->capacity }} Guests
            </div>

            <div>
                <strong>Cuisine:</strong>

                {{ $restaurant->cuisine }}

            </div>

            <div>
                <strong>Dress Code:</strong>

                {{ $restaurant->dress_code }}

            </div>

        </div>

    </div>

</section>

<section class="bg-gray-100 py-16">

    <div class="max-w-7xl mx-auto">

        <h2
            class="text-3xl
            font-bold
            text-center
            mb-10"
        >

            Featured Dishes

        </h2>

        <div
            class="grid
            md:grid-cols-3
            gap-8"
        >

            <!-- Dish 1 -->

            <div class="bg-white rounded-xl shadow">

                <img
                    src="{{ asset('images/tilapia.jpg') }}"
                    class="rounded-t-xl h-56 w-full object-cover"
                >

                <div class="p-5">

                    <h3 class="font-bold">
                        Grilled Tilapia
                    </h3>

                    <p class="text-gray-500">
                        Served with banku.
                    </p>

                    <div class="mt-4 font-bold">

                        GHS 120

                    </div>

                </div>

            </div>

            <!-- Repeat for more dishes -->

        </div>

    </div>

</section>

<section class="max-w-7xl mx-auto py-16">

    <h2 class="text-3xl font-bold mb-8">
        Our Restaurant Tables
    </h2>

    <div class="grid md:grid-cols-3 gap-6">

        @foreach($restaurant->tables as $table)

            <div class="bg-white shadow rounded-xl p-6">

                <h3 class="text-xl font-bold">
                    {{ $table->table_number }}
                </h3>

                <p>
                    Capacity: {{ $table->capacity }}
                </p>

                <p>
                    Location: {{ $table->location }}
                </p>

                <span class="inline-block mt-3 px-3 py-1 rounded-full bg-green-100 text-green-700">
                    {{ ucfirst($table->status) }}
                </span>

            </div>

        @endforeach

    </div>

</section>

<section class="py-16">

    <div class="max-w-7xl mx-auto">

        <h2
            class="text-3xl
            font-bold
            text-center
            mb-10"
        >

            Facilities

        </h2>

        <div
            class="grid
            md:grid-cols-3
            gap-6
            text-center"
        >

            <div>🍷 Bar</div>

            <div>📶 Free WiFi</div>

            <div>🎵 Live Music</div>

            <div>❄ Air Conditioned</div>

            <div>👶 Family Friendly</div>

            <div>🚗 Parking</div>

        </div>

    </div>

</section>

<section class="bg-gray-100 py-16">

    <div class="max-w-7xl mx-auto">

        <h2
            class="text-3xl
            font-bold
            text-center
            mb-10"
        >

            Gallery

        </h2>

        <div
            class="grid
            md:grid-cols-4
            gap-5"
        >

            <img src="{{ asset('images/gallery1.jpg') }}">

            <img src="{{ asset('images/gallery2.jpg') }}">

            <img src="{{ asset('images/gallery3.jpg') }}">

            <img src="{{ asset('images/gallery4.jpg') }}">

        </div>

    </div>

</section>

<section class="py-20">

    <div class="text-center">

        <h2 class="text-4xl font-bold">

            Ready for a Great Dining Experience?

        </h2>

        <div class="mt-10 flex justify-center gap-5">

            <!-- <a
                href="/restaurant/reservations"
                class="bg-blue-600
                text-white
                px-8 py-4
                rounded-lg"
            >

                Reserve a Table

            </a> -->

            <a
                href="{{ route('restaurant.reserve') }}"
                class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
            >
                Reserve a Table
            </a>

            <a
                href="/restaurant/menu"
                class="bg-green-600
                text-white
                px-8 py-4
                rounded-lg"
            >

                Order Food

            </a>

        </div>

    </div>

</section>

</x-guest-layout>