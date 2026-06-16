<x-guest-layout>

<div class="max-w-7xl mx-auto py-12">

    <h1 class="text-3xl font-bold mb-8">

        Conference Rooms

    </h1>

    <div class="grid md:grid-cols-3 gap-6">

        @foreach($rooms as $room)

            <div
                class="bg-white rounded-xl shadow overflow-hidden"
            >

                <img
                    src="{{ asset('storage/' . $room->image) }}"
                    class="h-56 w-full object-cover"
                >

                <div class="p-5">

                    <h2
                        class="font-bold text-xl"
                    >
                        {{ $room->name }}
                    </h2>

                    <p
                        class="text-gray-600 mt-2"
                    >
                        {{ $room->description }}
                    </p>

                    @if(
                        $room->facilities
                            ->count()
                    )

                    <div class="mt-4">

                        <h4
                            class="font-semibold mb-2"
                        >

                            Facilities

                        </h4>

                        <div
                            class="flex flex-wrap gap-2"
                        >

                            @foreach(
                                $room->facilities
                                as $facility
                            )

                                <span
                                    class="bg-blue-50
                                    text-blue-700
                                    px-3 py-1
                                    rounded-full
                                    text-sm"
                                >

                                    ✓
                                    {{ $facility->name }}

                                </span>

                            @endforeach

                        </div>

                    </div>

                    @endif

                    <p class="mt-4">

                        Capacity:
                        {{ $room->capacity }}

                    </p>

                    <p>

                        GHS
                        {{ number_format(
                            $room->price_per_hour,
                            2
                        ) }}
                        / hour

                    </p>

                    <a
                        href="{{ route('conference.book', $room->id) }}"
                        class="bg-blue-600 text-white px-5 py-2 rounded-lg inline-block mt-4"
                    >
                        Book Room
                    </a>

                </div>

            </div>

        @endforeach

    </div>

</div>

</x-guest-layout>