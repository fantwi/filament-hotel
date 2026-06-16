<x-guest-layout>

<div class="max-w-7xl mx-auto py-12 px-4">

<h1 class="text-3xl font-bold mb-2">
{{ $type->name }} Rooms
</h1>

<p class="text-gray-500 mb-8">
Choose an available room <br />
@if($rooms->count() < 3)

<span class="text-yellow-600 text-sm">
Only few rooms left
</span>

@endif
</p>

@if($rooms->isEmpty())

<div class="bg-red-50 border border-red-200 text-red-700 p-6 rounded-xl">

    No available rooms right now.

</div>

@else

<div class="grid md:grid-cols-3 gap-6">

@foreach($rooms as $room)

<div class="bg-white rounded-xl shadow p-6">

    <h3 class="text-2xl font-bold mb-3">
        Room {{ $room->room_number }}
    </h3>

    <!-- <p class="text-gray-500 mb-3">
        Floor:
        {{ $room->floor ?? 'N/A' }}
    </p> -->

    <div class="mb-4">

        <span class="text-green-600 font-semibold">
            Available
        </span>

    </div>

    <div class="text-blue-600 font-bold text-lg mb-5">

        GHS {{ number_format($type->price_per_night ?? 0, 2) }}

        <span class="text-sm text-gray-500">
            / night
        </span>

    </div>

    <a
        href="{{ route('booking.select', $room->id) }}"
        class="block text-center bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition"
    >

        Reserve Room

    </a>

</div>

@endforeach

</div>

@endif

</div>

</x-guest-layout>