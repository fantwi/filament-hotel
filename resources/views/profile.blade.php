<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div x-data="{ tab: 'profile' }" class="mx-auto max-w-6xl overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5">
            <header class="bg-gradient-to-br from-blue-700 to-indigo-900 p-5 text-white sm:p-8">
                <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:text-left">
                    <img src="{{ $guest?->profile_photo ? asset('storage/' . $guest->profile_photo) : ($user->profile_photo ? asset('storage/' . $user->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name)) }}" alt="{{ $user->name }}" class="h-20 w-20 rounded-full border-4 border-white/80 object-cover sm:h-24 sm:w-24">
                    <div><p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-100">Guest account</p><h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $user->name }}</h1><p class="mt-1 text-sm text-blue-100">{{ $user->email }}</p></div>
                </div>
            </header>

            <nav class="flex gap-2 overflow-x-auto border-b border-gray-200 px-4 py-3 sm:px-6" aria-label="Profile sections">
                <button @click="tab = 'profile'" :class="tab === 'profile' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'" class="min-h-11 shrink-0 rounded-xl px-4 py-2 text-sm font-semibold">Profile</button>
                <button @click="tab = 'bookings'" :class="tab === 'bookings' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'" class="min-h-11 shrink-0 rounded-xl px-4 py-2 text-sm font-semibold">Bookings</button>
                <button @click="tab = 'payments'" :class="tab === 'payments' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'" class="min-h-11 shrink-0 rounded-xl px-4 py-2 text-sm font-semibold">Payments</button>
                <button @click="tab = 'security'" :class="tab === 'security' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'" class="min-h-11 shrink-0 rounded-xl px-4 py-2 text-sm font-semibold">Security</button>
            </nav>

            <div class="p-5 sm:p-8">
                <div x-show="tab === 'profile'">
                    <h2 class="text-xl font-bold text-gray-900">Profile details</h2><p class="mt-1 text-sm text-gray-600">Keep your contact details and profile photo up to date.</p>
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 max-w-xl space-y-5">@csrf
                        <div><label for="phone_number" class="block text-sm font-semibold text-gray-800">Phone number</label><input id="phone_number" type="tel" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                        <div><label for="profile_photo" class="block text-sm font-semibold text-gray-800">Profile picture</label><input id="profile_photo" type="file" name="profile_photo" accept="image/*" class="mt-2 block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 hover:file:bg-blue-100"></div>
                        <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto">Save profile</button>
                    </form>
                </div>

                <div x-cloak x-show="tab === 'bookings'" class="space-y-8">
                    <div><h2 class="text-xl font-bold text-gray-900">Hotel bookings</h2><div class="mt-4 space-y-3">@forelse ($hotelBookings as $booking)<div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-4"><p class="font-semibold text-gray-900">Room {{ $booking->room_id }}</p><p class="text-sm font-semibold text-blue-700">GHS {{ number_format($booking->total_price, 2) }}</p></div>@empty<p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-600">No hotel bookings yet.</p>@endforelse</div></div>
                    <div><h2 class="text-xl font-bold text-gray-900">Conference bookings</h2><div class="mt-4 space-y-3">@forelse ($conferenceBookings as $booking)<div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-4"><p class="font-semibold text-gray-900">{{ $booking->room?->name }}</p><p class="text-sm font-semibold text-blue-700">GHS {{ number_format($booking->total_price, 2) }}</p></div>@empty<p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-600">No conference bookings yet.</p>@endforelse</div></div>
                </div>

                <div x-cloak x-show="tab === 'payments'"><h2 class="text-xl font-bold text-gray-900">Payment history</h2><div class="mt-4 space-y-3">@forelse ($payments as $payment)<div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-4"><p class="font-semibold text-gray-900">{{ $payment->method }}</p><p class="text-sm font-semibold text-green-700">GHS {{ number_format($payment->amount, 2) }}</p></div>@empty<p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-600">No payments recorded yet.</p>@endforelse</div></div>

                <div x-cloak x-show="tab === 'security'"><h2 class="text-xl font-bold text-gray-900">Change password</h2><p class="mt-1 text-sm text-gray-600">Use a strong, unique password to keep your account secure.</p><form method="POST" action="{{ route('profile.password') }}" class="mt-6 max-w-xl space-y-5">@csrf
                    <div><label for="current_password" class="block text-sm font-semibold text-gray-800">Current password</label><input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                    <div><label for="password" class="block text-sm font-semibold text-gray-800">New password</label><input id="password" type="password" name="password" required autocomplete="new-password" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                    <div><label for="password_confirmation" class="block text-sm font-semibold text-gray-800">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                    <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-red-600 px-5 py-3 font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 sm:w-auto">Update password</button>
                </form></div>
            </div>
        </div>
    </section>
</x-guest-layout>
