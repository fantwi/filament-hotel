<x-guest-layout>

<section class="relative">

    <img
        src="{{ asset('images/restaurant-banner.jpg') }}"
        class="w-full h-[500px] object-cover"
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
            Restaurant
        </h1>

        <p class="text-xl">
            Fine Dining • Local & International Cuisine
        </p>

    </div>

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
                6:00 AM
            </div>

            <div>
                <strong>Closing:</strong>
                11:00 PM
            </div>

            <div>
                <strong>Capacity:</strong>
                120 Guests
            </div>

            <div>
                <strong>Cuisine:</strong>

                African • Continental • Chinese • Italian

            </div>

            <div>
                <strong>Dress Code:</strong>

                Smart Casual

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

            <a
                href="/restaurant/reservations"
                class="bg-blue-600
                text-white
                px-8 py-4
                rounded-lg"
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