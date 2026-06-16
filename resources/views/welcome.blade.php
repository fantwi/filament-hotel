<x-guest-layout>
<head>
    <meta charset="UTF-8">
    <title>Hotel Booking</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50">

<!-- 🔝 NAVBAR -->
<!-- <nav class="flex justify-between items-center p-4 bg-white shadow">

    <h1 class="text-xl font-bold">🏨 My Hotel</h1>

    <div class="space-x-4">

        @auth
            <a href="/dashboard" class="text-gray-800">Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="text-gray-700">Login</a>
            <a href="{{ route('register') }}"
               class="bg-gray-800 text-white px-4 py-2 rounded">
               Sign Up
            </a>
        @endauth

    </div>

</nav> -->

<!-- 🌄 HERO -->
<section class="text-center py-16 bg-blue-600 dark:bg-blue-700 text-white">

    <h2 class="text-4xl font-bold mb-4">
        Welcome to Our Hotel
    </h2>

    <p class="mb-6">
        Experience comfort, luxury, and convenience.
    </p>

    <a href="/rooms"
       class="bg-white text-gray-800 px-6 py-3 rounded font-semibold">
        Book a Room
    </a>

</section>

<!-- 🛏 ROOM TYPES -->
<section class="max-w-6xl mx-auto py-12">

    <h3 class="text-3xl font-bold text-center mb-8">
        Our Rooms
    </h3>

    <div class="grid md:grid-cols-3 gap-8">

        @foreach($roomTypes as $type)

            <div class="bg-white rounded-xl shadow overflow-hidden">

                <img
                src="{{ asset('storage/'.$type->image) }}"
                class="w-full h-64 object-cover"
                alt="{{ $type->name }}"
                >

                <div class="p-6">

                    <h4 class="text-xl font-bold mb-2">
                    {{ $type->name }}
                    </h4>

                    <p class="text-gray-600 mb-4">
                    {{ $type->description }}
                    </p>

                    <!-- Facilities -->
                    @if($type->facilities->count())
                        <div class="mb-4">
                            <h5 class="text-sm font-semibold text-gray-700 mb-2">
                                Facilities
                            </h5>
                            <div class="flex flex-wrap gap-2">
                                @foreach(
                                    $type->facilities
                                    as $facility
                                )
                                    <span
                                        class="inline-flex items-center
                                        gap-2
                                        bg-blue-50
                                        text-blue-700
                                        px-3 py-1
                                        rounded-full
                                        text-sm"
                                    >
                                        <span>✨</span>
                                        {{ $facility->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <!-- end facilities -->

                    <div class="flex justify-between items-center">

                        <span class="font-bold text-blue-600">
                        GHS {{ number_format($type->price_per_night,2) }}
                        </span>

                        <a href="{{ route('rooms.available',$type->id) }}"
                        class="bg-blue-600 dark:bg-blue-700 text-white px-4 py-2 rounded">
                        Book Now
                        </a>

                    </div>

                </div>

            </div>

        @endforeach

    </div>

</section>
<!-- <section class="max-w-6xl mx-auto py-12">

    <h3 class="text-2xl font-bold mb-6 text-center">
        Our Room Types
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        @foreach ($roomTypes as $type)

            <div class="bg-white p-4 rounded shadow">

                <h4 class="text-lg font-semibold">
                    {{ $type->name }}
                </h4>

                <p class="text-gray-600 mb-2">
                    {{ $type->description }}
                </p>

                <p class="font-bold text-blue-800">
                    GHS {{ number_format($type->price, 2) }} / night
                </p>

            </div>

        @endforeach

    </div>

</section> -->

<!-- 🏊 FACILITIES -->
<section class="bg-gray-100 py-12">

    <h3 class="text-2xl font-bold text-center mb-6">
        Facilities & Services
    </h3>

    <div class="flex flex-wrap justify-center gap-6 text-center">

        <div>🏊 Swimming Pool</div>
        <div>🍽 Restaurant</div>
        <div>📶 Free WiFi</div>
        <div>🚗 Parking</div>
        <div>🧺 Laundry</div>

    </div>

</section>

<!-- 📢 CTA -->
<section class="text-center py-12">

    <h3 class="text-xl font-semibold mb-4">
        Ready to book your stay?
    </h3>

    <a href="/rooms"
       class="bg-blue-600 dark:bg-blue-700 text-white px-6 py-3 rounded">
        Browse Rooms
    </a>

</section>

<!-- 🔚 FOOTER -->
<footer class="text-center p-4 bg-white border-t">

    <p class="text-gray-500">
        © {{ date('Y') }} My Hotel. All rights reserved.
    </p>

</footer>

</body>
</x-guest-layout>