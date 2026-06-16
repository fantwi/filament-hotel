<x-guest-layout>

<div class="max-w-5xl mx-auto py-12">

<img
src="{{ asset('storage/'.$type->image) }}"
class="rounded-xl mb-8 w-full h-96 object-cover"
>

<h1 class="text-4xl font-bold mb-4">
{{ $type->name }}
</h1>

<p class="mb-6 text-gray-600">
{{ $type->description }}
</p>

<!-- Room Facilities -->
<div class="mt-6">

    <h3 class="font-bold mb-3">

        Room Facilities

    </h3>

    <div class="grid grid-cols-2 gap-3">

        @foreach(
            $roomType->facilities
            as $facility
        )

            <div
                class="flex items-center gap-2"
            >

                <span>
                    ✓
                </span>

                <span>
                    {{ $facility->name }}
                </span>

            </div>

        @endforeach

    </div>

</div>
<!-- End Room Facilities -->

<!-- <div class="mb-6">
Price:
<strong>
GHS {{ number_format($type->price_per_night ?? 0,2) }}/night
</strong>
</div> -->

<div class="text-blue-600 font-bold text-lg">
    Price:
    <strong>
    GHS {{ number_format($type->price_per_night ?? 0, 2) }}
    </strong>
    <span class="text-sm text-gray-500">
        per night
    </span>
</div>

<div class="mb-6">
Available Rooms:
<span class="text-green-600 font-bold">
{{ $availableRooms }}
</span>
</div>

<a href="/booking/create?room_type={{ $type->id }}"
class="bg-blue-600  dark:bg-blue-700 text-white px-6 py-3 rounded">
Reserve This Room
</a>

</div>

</x-guest-layout>