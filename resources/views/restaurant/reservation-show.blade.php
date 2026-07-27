<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="mx-auto max-w-3xl overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5">
            <header class="bg-gradient-to-br from-blue-700 to-indigo-900 p-6 text-white sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-100">Restaurant reservation</p>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <h1 class="text-2xl font-bold sm:text-3xl">Reservation #{{ $reservation->id }}</h1>
                    <span class="rounded-full bg-white/15 px-3 py-1 text-sm font-semibold capitalize">{{ str_replace('_', ' ', $reservation->status) }}</span>
                </div>
            </header>

            <div class="p-5 sm:p-8">
                <dl class="grid gap-5 text-sm sm:grid-cols-2">
                    <div><dt class="font-semibold text-slate-500">Restaurant</dt><dd class="mt-1 text-base font-semibold text-slate-900">{{ $reservation->restaurant?->name }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Table</dt><dd class="mt-1 text-base font-semibold text-slate-900">{{ $reservation->table?->table_number ?? 'Assigned table' }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Date</dt><dd class="mt-1 text-base font-semibold text-slate-900">{{ $reservation->reservation_date?->format('D, d M Y') }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Time</dt><dd class="mt-1 text-base font-semibold text-slate-900">{{ $reservation->reservation_time }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Guests</dt><dd class="mt-1 text-base font-semibold text-slate-900">{{ $reservation->number_of_guests }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Reservation fee</dt><dd class="mt-1 text-base font-semibold text-slate-900">GHS {{ number_format($reservation->reservation_fee, 2) }}</dd></div>
                    @if ($reservation->special_requests)<div class="sm:col-span-2"><dt class="font-semibold text-slate-500">Special requests</dt><dd class="mt-1 text-base text-slate-900">{{ $reservation->special_requests }}</dd></div>@endif
                </dl>

                <div class="mt-8 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-50">Back to dashboard</a>
                    @if ($reservation->payment_status !== 'completed' && $reservation->hold_status !== 'expired')
                        <a href="{{ route('restaurant.payment', $reservation) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">Complete payment</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
