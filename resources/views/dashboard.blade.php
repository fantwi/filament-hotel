<x-guest-layout>
    <style>[x-cloak] { display: none !important; }</style>

    @php
        $user = auth()->user();
        $hotelBookings = $hotelBookings ?? collect();
        $conferenceBookings = $conferenceBookings ?? collect();
        $restaurantReservations = $restaurantReservations ?? collect();
        $restaurantOrders = $restaurantOrders ?? collect();
        $totalBookings = $totalBookings ?? 0;
        $totalConfirmedBookings = $totalConfirmedBookings ?? 0;
        $totalRestaurantReservations = $totalRestaurantReservations ?? 0;
        $totalRestaurantOrders = $totalRestaurantOrders ?? 0;
        $totalSpent = $totalSpent ?? 0;
        $outstandingBalance = $outstandingBalance ?? 0;
        $statusClass = fn ($status) => match ($status) {
            'confirmed', 'completed', 'served' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-400/15 dark:text-emerald-200 dark:ring-emerald-300/20',
            'pending', 'preparing' => 'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-400/15 dark:text-amber-200 dark:ring-amber-300/20',
            'ready' => 'bg-sky-100 text-sky-800 ring-sky-600/20 dark:bg-sky-400/15 dark:text-sky-200 dark:ring-sky-300/20',
            'cancelled', 'expired' => 'bg-rose-100 text-rose-800 ring-rose-600/20 dark:bg-rose-400/15 dark:text-rose-200 dark:ring-rose-300/20',
            default => 'bg-slate-100 text-slate-700 ring-slate-600/10 dark:bg-slate-700 dark:text-slate-200 dark:ring-white/10',
        };
    @endphp

    <main class="min-h-screen bg-slate-50 py-5 text-slate-900 transition-colors dark:bg-[#161b48] dark:text-slate-100 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:space-y-8 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#161b48] via-indigo-900 to-blue-700 px-4 py-6 text-white shadow-2xl shadow-indigo-950/25 sm:px-8 sm:py-10">
                <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-cyan-300/10 blur-3xl"></div>
                <div class="absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-violet-400/15 blur-3xl"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex items-start gap-3 sm:items-center sm:gap-5">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-xl font-bold ring-1 ring-white/25 backdrop-blur sm:h-20 sm:w-20 sm:text-3xl">
                            {{ strtoupper(substr($user?->first_name ?? 'G', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-100">Guest dashboard</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-4xl">Welcome back, {{ $user?->first_name ?? 'Guest' }}.</h1>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-indigo-100 sm:text-base">Manage your stays, events, restaurant reservations, and food orders in one place.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
                        <a href="{{ route('rooms.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-indigo-950 shadow-lg shadow-indigo-950/20 transition hover:bg-indigo-50">Book a room</a>
                        <a href="{{ route('restaurant.menu') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">Order food</a>
                    </div>
                </div>
            </section>

            @if ($outstandingBalance > 0)
                <section class="flex flex-col gap-4 rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm dark:border-rose-400/20 dark:bg-rose-400/10 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div><p class="font-semibold text-rose-900 dark:text-rose-100">Payment action needed</p><p class="mt-1 text-sm text-rose-700 dark:text-rose-200">You have GHS {{ number_format($outstandingBalance, 2) }} awaiting payment.</p></div>
                    <a href="{{ route('payments.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">View payments</a>
                </section>
            @else
                <section class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-100 sm:px-6">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-lg font-bold text-white">✓</span>
                    <p class="text-sm font-medium">You are all caught up — there are no outstanding payments.</p>
                </section>
            @endif

            <section class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-white/10 sm:p-5"><p class="text-xs font-medium text-slate-500 dark:text-slate-400 sm:text-sm">Total bookings</p><div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><p class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $totalBookings }}</p><span class="w-fit rounded-xl bg-indigo-100 px-2.5 py-1.5 text-xs text-indigo-700 dark:bg-indigo-400/15 dark:text-indigo-200 sm:px-3 sm:py-2">All stays</span></div></article>
                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-white/10 sm:p-5"><p class="text-xs font-medium text-slate-500 dark:text-slate-400 sm:text-sm">Confirmed</p><div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><p class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $totalConfirmedBookings }}</p><span class="w-fit rounded-xl bg-emerald-100 px-2.5 py-1.5 text-xs text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-200 sm:px-3 sm:py-2">Ready to go</span></div></article>
                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-white/10 sm:p-5"><p class="text-xs font-medium text-slate-500 dark:text-slate-400 sm:text-sm">Restaurant activity</p><div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><p class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $totalRestaurantReservations + $totalRestaurantOrders }}</p><span class="w-fit rounded-xl bg-orange-100 px-2.5 py-1.5 text-xs text-orange-700 dark:bg-orange-400/15 dark:text-orange-200 sm:px-3 sm:py-2">Dining</span></div></article>
                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-white/10 sm:p-5"><p class="text-xs font-medium text-slate-500 dark:text-slate-400 sm:text-sm">Total spent</p><div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><p class="text-lg font-bold tracking-tight sm:text-2xl">GHS {{ number_format($totalSpent, 2) }}</p><span class="w-fit rounded-xl bg-cyan-100 px-2.5 py-1.5 text-xs text-cyan-700 dark:bg-cyan-400/15 dark:text-cyan-200 sm:px-3 sm:py-2">Paid</span></div></article>
            </section>

            <section class="grid gap-3 sm:grid-cols-3 sm:gap-4">
                <a href="{{ route('rooms.index') }}" class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-white/10"><p class="text-sm font-semibold text-indigo-600 dark:text-indigo-300">Stay with us</p><p class="mt-1 text-lg font-bold">Find your next room</p><p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Explore room types and choose dates that work for you.</p><span class="mt-4 inline-block text-sm font-semibold text-indigo-700 dark:text-indigo-300">Browse rooms →</span></a>
                <a href="{{ route('restaurant.reserve') }}" class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-white/10"><p class="text-sm font-semibold text-emerald-600 dark:text-emerald-300">Dine your way</p><p class="mt-1 text-lg font-bold">Reserve a table</p><p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Reserve a table before you arrive at the restaurant.</p><span class="mt-4 inline-block text-sm font-semibold text-emerald-700 dark:text-emerald-300">Reserve now →</span></a>
                <a href="{{ route('restaurant.menu') }}" class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-white/10"><p class="text-sm font-semibold text-orange-600 dark:text-orange-300">Room service & dining</p><p class="mt-1 text-lg font-bold">Order a favourite meal</p><p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">View the menu and track each food order here.</p><span class="mt-4 inline-block text-sm font-semibold text-orange-700 dark:text-orange-300">View menu →</span></a>
            </section>

            <section x-data="{ tab: 'hotel' }" class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-white/10">
                <div class="border-b border-slate-200 p-4 dark:border-white/10 sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-300">Your activity</p><h2 class="mt-1 text-2xl font-bold tracking-tight">Bookings and orders</h2></div><a href="{{ route('payments.index') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 dark:text-indigo-300 dark:hover:text-indigo-100">Payment history →</a></div>
                    <div class="mt-5 flex snap-x snap-mandatory gap-2 overflow-x-auto pb-1" role="tablist">
                        <button @click="tab = 'hotel'" :class="tab === 'hotel' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'" class="min-h-11 shrink-0 snap-start rounded-xl px-4 py-2 text-sm font-semibold transition">Hotel</button>
                        <button @click="tab = 'conference'" :class="tab === 'conference' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'" class="min-h-11 shrink-0 snap-start rounded-xl px-4 py-2 text-sm font-semibold transition">Conference</button>
                        <button @click="tab = 'restaurant'" :class="tab === 'restaurant' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'" class="min-h-11 shrink-0 snap-start rounded-xl px-4 py-2 text-sm font-semibold transition">Reservations</button>
                        <button @click="tab = 'orders'" :class="tab === 'orders' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'" class="min-h-11 shrink-0 snap-start rounded-xl px-4 py-2 text-sm font-semibold transition">Food orders</button>
                    </div>
                </div>

                <div class="p-4 sm:p-7">
                    <div x-show="tab === 'hotel'" x-transition.opacity>
                        <div class="mb-5 flex items-center justify-between"><h3 class="text-lg font-bold">Hotel stays</h3><a href="{{ route('rooms.index') }}" class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Book a room →</a></div>
                        <div class="space-y-4">@forelse ($hotelBookings as $booking)<article class="rounded-2xl border border-slate-200 p-4 dark:border-white/10 sm:p-5"><div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><h4 class="font-bold">{{ $booking->room?->roomType?->name ?? 'Hotel room' }}</h4><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::parse($booking->check_in)->format('D, d M Y') }} – {{ \Carbon\Carbon::parse($booking->check_out)->format('D, d M Y') }}</p><p class="mt-2 font-semibold">GHS {{ number_format($booking->total_price, 2) }}</p></div><span class="w-fit rounded-full px-3 py-1 text-xs font-bold capitalize ring-1 {{ $statusClass($booking->status) }}">{{ str_replace('_', ' ', $booking->status) }}</span></div><div class="mt-4 flex flex-wrap gap-2">@if ($booking->payment_status === 'pending' && $booking->hold_status !== 'expired')<a href="{{ route('booking.payment', $booking) }}" class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Pay now</a>@endif<a href="{{ route('booking.invoice', $booking) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">Invoice</a></div></article>@empty<div class="rounded-2xl bg-slate-50 p-8 text-center dark:bg-slate-800/70"><p class="font-semibold">No hotel stays yet</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Your next getaway starts with a room.</p></div>@endforelse</div>
                    </div>

                    <div x-cloak x-show="tab === 'conference'" x-transition.opacity>
                        <div class="mb-5 flex items-center justify-between"><h3 class="text-lg font-bold">Conference bookings</h3><a href="{{ url('/conference-rooms') }}" class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Browse venues →</a></div>
                        <div class="space-y-4">@forelse ($conferenceBookings as $booking)<article class="rounded-2xl border border-slate-200 p-4 dark:border-white/10 sm:p-5"><div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><h4 class="font-bold">{{ $booking->room?->name ?? 'Conference room' }}</h4><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $booking->booking_date?->format('D, d M Y') }} · {{ $booking->start_time }} – {{ $booking->end_time }}</p><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $booking->attendees }} attendees · <span class="font-semibold text-slate-900 dark:text-white">GHS {{ number_format($booking->total_price, 2) }}</span></p></div><span class="w-fit rounded-full px-3 py-1 text-xs font-bold capitalize ring-1 {{ $statusClass($booking->status) }}">{{ str_replace('_', ' ', $booking->status) }}</span></div><div class="mt-4 flex flex-wrap gap-2">@if ($booking->payment_status === 'pending')<a href="{{ route('conference.payment', $booking) }}" class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Pay now</a>@endif<a href="{{ route('conference.invoice', $booking) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">Invoice</a></div></article>@empty<div class="rounded-2xl bg-slate-50 p-8 text-center dark:bg-slate-800/70"><p class="font-semibold">No conference bookings yet</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Find a space for your next event.</p></div>@endforelse</div>
                    </div>

                    <div x-cloak x-show="tab === 'restaurant'" x-transition.opacity>
                        <div class="mb-5 flex items-center justify-between"><h3 class="text-lg font-bold">Restaurant reservations</h3><a href="{{ route('restaurant.reserve') }}" class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Reserve a table →</a></div>
                        <div class="space-y-4">@forelse ($restaurantReservations as $reservation)<article class="rounded-2xl border border-slate-200 p-4 dark:border-white/10 sm:p-5"><div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><h4 class="font-bold">{{ $reservation->restaurant?->name ?? 'Restaurant reservation' }}</h4><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Table {{ $reservation->table?->table_number ?? '—' }} · {{ $reservation->reservation_date?->format('D, d M Y') }} · {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}</p><p class="mt-2 font-semibold">GHS {{ number_format($reservation->reservation_fee, 2) }}</p></div><span class="w-fit rounded-full px-3 py-1 text-xs font-bold capitalize ring-1 {{ $statusClass($reservation->status) }}">{{ str_replace('_', ' ', $reservation->status) }}</span></div><div class="mt-4 flex flex-wrap gap-2"><a href="{{ route('restaurant.reservations.show', $reservation) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">View details</a>@if ($reservation->payment_status !== 'completed' && $reservation->hold_status !== 'expired')<a href="{{ route('restaurant.payment', $reservation) }}" class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Pay now</a>@endif@if ($reservation->status === 'pending')<form method="POST" action="{{ route('restaurant.reservations.cancel', $reservation) }}">@csrf @method('PATCH')<button onclick="return confirm('Cancel this reservation?')" class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-400/10">Cancel</button></form>@endif</div></article>@empty<div class="rounded-2xl bg-slate-50 p-8 text-center dark:bg-slate-800/70"><p class="font-semibold">No table reservations yet</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Reserve a table for a relaxed dining experience.</p></div>@endforelse</div>
                    </div>

                    <div x-cloak x-show="tab === 'orders'" x-transition.opacity>
                        <div class="mb-5 flex items-center justify-between"><h3 class="text-lg font-bold">Restaurant food orders</h3><a href="{{ route('restaurant.menu') }}" class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Order food →</a></div>
                        <div class="space-y-4">@forelse ($restaurantOrders as $order)<article class="rounded-2xl border border-slate-200 p-4 dark:border-white/10 sm:p-5"><div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><h4 class="font-bold">Order {{ $order->order_number }}</h4><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $order->items->sum('quantity') }} item(s) · GHS {{ number_format($order->total, 2) }}</p><p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Payment: <span class="font-semibold capitalize">{{ str_replace('_', ' ', $order->payment_status) }}</span></p></div><span class="w-fit rounded-full px-3 py-1 text-xs font-bold capitalize ring-1 {{ $statusClass($order->status) }}">{{ str_replace('_', ' ', $order->status) }}</span></div><div class="mt-4 border-t border-slate-200 pt-4 dark:border-white/10">@foreach ($order->items as $item)<div class="flex items-center justify-between gap-4 py-2 text-sm"><span class="text-slate-600 dark:text-slate-300">{{ $item->quantity }} × {{ $item->menuItem?->name ?? 'Menu item' }}</span><span class="font-semibold">GHS {{ number_format($item->total_price, 2) }}</span></div>@endforeach</div>@if ($order->payment_status !== 'completed')<a href="{{ route('restaurant.orders.confirmation', $order) }}" class="mt-4 inline-flex rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Complete payment</a>@endif</article>@empty<div class="rounded-2xl bg-slate-50 p-8 text-center dark:bg-slate-800/70"><p class="font-semibold">No food orders yet</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Your meal orders will appear here.</p></div>@endforelse</div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</x-guest-layout>
