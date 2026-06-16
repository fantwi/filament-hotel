<x-guest-layout>

<div class="max-w-7xl mx-auto py-12">

<h2 class="text-3xl font-bold mb-8">
Available Rooms Types
</h2>

<div class="grid md:grid-cols-2 gap-8">

@foreach($roomTypes as $roomType)

<div class="flex bg-white rounded-xl shadow overflow-hidden">

<img
src="{{ asset('storage/'.$roomType->image) }}"
class="w-1/3 object-cover"
>

<div class="p-6 flex-1">

<h3 class="text-xl font-bold">
{{ $roomType->name }}
</h3>

<p class="text-gray-600 my-3">
{{ $roomType->description }}
</p>

<div class="flex flex-wrap gap-2 mb-4">
    @foreach(
        $roomType->facilities
        as $facility
    )
        <span
            class="bg-gray-100
            text-gray-700
            px-3 py-1
            rounded-full
            text-sm"
        >
            ✓ {{ $facility->name }}
        </span>
    @endforeach
</div>

<div class="flex justify-between items-center">

    <!-- <div>
    GHS {{ number_format($roomType->price_per_night ?? 0,2) }}
    </div> -->

    <div class="text-blue-600 font-bold text-lg">
        GHS {{ number_format($roomType->price_per_night ?? 0, 2) }}
        <span class="text-sm text-gray-500">
            / night
        </span>
    </div>

</div>

<div class="flex justify-between items-center pt-4">

    <div class="flex gap-10">

        <!-- VIEW ROOMS -->
        <a
            href="{{ route('rooms.available', $roomType->id) }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition"
        >

            View Rooms

        </a>

        <!-- AVAILABILITY CALENDAR -->
        <a
            href="{{ route('rooms.calendar', $roomType->id) }}"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg flex items-center gap-2 transition"
        >

            <span>📅</span>

            <span>
                Availability
            </span>

        </a>

    </div>

</div>

</div>

</div>

@endforeach

</div>

</div>

</x-guest-layout>