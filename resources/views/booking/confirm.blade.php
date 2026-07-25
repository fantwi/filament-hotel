<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto w-full max-w-xl rounded-2xl bg-white p-6 text-center shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-10">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-2xl text-green-700" aria-hidden="true">✓</div>
            <p class="mt-5 text-sm font-semibold uppercase tracking-[0.2em] text-green-700">Reservation confirmed</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Your stay is reserved</h1>
            <p class="mt-3 text-sm leading-6 text-gray-600">We have successfully received your booking request and will keep your room ready for you.</p>

            <div class="mt-7 rounded-xl bg-slate-50 px-4 py-4 text-left">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Booking reference</p>
                <p class="mt-1 text-lg font-bold text-gray-900">#{{ $booking->id }}</p>
            </div>

            <a href="{{ route('dashboard') }}" class="mt-7 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Go to my dashboard</a>
        </div>
    </section>
</x-guest-layout>
