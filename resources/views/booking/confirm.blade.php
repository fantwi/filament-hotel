<x-guest-layout>

<div class="mx-auto max-w-xl px-4 py-8 text-center sm:px-6 sm:py-12">

<h1 class="text-3xl font-bold text-green-600 mb-4">
Booking Confirmed 🎉
</h1>

<p class="mb-4">
Booking ID: #{{ $booking->id }}
</p>

<p>
Your stay has been successfully reserved.
</p>

<a href="/dashboard" class="mt-6 inline-block text-blue-600">
Back to Home
</a>

</div>

</x-guest-layout>
