@php($restaurantCartCount = collect(session('cart', []))->sum(fn (array $line) => max(1, (int) ($line['quantity'] ?? 1))))

<nav x-data="{ open: false, accountOpen: false, roomsOpen: false, restaurantOpen: false, conferenceOpen: false, mobileRoomsOpen: false, mobileRestaurantOpen: false, mobileConferenceOpen: false, dark: document.documentElement.classList.contains('dark'), toggleTheme() { this.dark = !this.dark; document.documentElement.classList.toggle('dark', this.dark); localStorage.setItem('guest-theme', this.dark ? 'dark' : 'light') } }" class="fixed inset-x-0 top-0 z-50 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
    <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <a href="/" class="shrink-0 text-lg font-bold text-gray-900 sm:text-xl dark:text-white">My Hotel</a>

        <div class="hidden items-center gap-2 text-sm font-medium text-gray-600 dark:text-slate-100 lg:flex">
            <div class="relative" @click.outside="roomsOpen = false">
                <button type="button" @click="roomsOpen = !roomsOpen" class="inline-flex items-center gap-1 rounded-lg px-3 py-2 hover:bg-gray-100 hover:text-blue-600">Rooms <span aria-hidden="true">⌄</span></button>
                <div x-show="roomsOpen" x-cloak x-transition class="absolute left-0 mt-2 w-52 overflow-hidden rounded-xl border bg-white py-1 text-gray-700 shadow-lg dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <a href="/rooms" class="block px-4 py-2.5 hover:bg-gray-50">Browse Rooms</a>
                </div>
            </div>
            <div class="relative" @click.outside="restaurantOpen = false">
                <button type="button" @click="restaurantOpen = !restaurantOpen" class="inline-flex items-center gap-1 rounded-lg px-3 py-2 hover:bg-gray-100 hover:text-blue-600">Restaurant <span aria-hidden="true">⌄</span></button>
                <div x-show="restaurantOpen" x-cloak x-transition class="absolute left-0 mt-2 w-56 overflow-hidden rounded-xl border bg-white py-1 text-gray-700 shadow-lg dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <a href="{{ route('restaurant') }}" class="block px-4 py-2.5 hover:bg-gray-50">Restaurant Home</a>
                    <a href="{{ route('restaurant.menu') }}" class="block px-4 py-2.5 hover:bg-gray-50">Menu</a>
                    <a href="{{ route('restaurant.tables') }}" class="block px-4 py-2.5 hover:bg-gray-50">Restaurant Tables</a>
                    <a href="{{ route('restaurant.gallery') }}" class="block px-4 py-2.5 hover:bg-gray-50">Gallery</a>
                    <a href="{{ route('restaurant.reserve') }}" class="block px-4 py-2.5 hover:bg-gray-50">Reserve a Table</a>
                    <a href="{{ route('cart.index') }}" class="block px-4 py-2.5 hover:bg-gray-50">Food Cart ({{ count(session('cart', [])) }})</a>
                </div>
            </div>
            <div class="relative" @click.outside="conferenceOpen = false">
                <button type="button" @click="conferenceOpen = !conferenceOpen" class="inline-flex items-center gap-1 rounded-lg px-3 py-2 hover:bg-gray-100 hover:text-blue-600">Conferences <span aria-hidden="true">⌄</span></button>
                <div x-show="conferenceOpen" x-cloak x-transition class="absolute left-0 mt-2 w-56 overflow-hidden rounded-xl border bg-white py-1 text-gray-700 shadow-lg dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <a href="/conference-rooms" class="block px-4 py-2.5 hover:bg-gray-50">Conference Rooms</a>
                </div>
            </div>
            <a href="/contact" class="hover:text-blue-600">Contact</a>
        </div>

        <div class="hidden items-center gap-3 lg:flex">
            @guest
                <button type="button" @click="toggleTheme()" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 transition hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:text-slate-100 dark:hover:bg-slate-800" :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'" :title="dark ? 'Switch to light mode' : 'Switch to dark mode'">
                    <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36-6.36-.71.71M6.35 17.65l-.71.71m12.72 0-.71-.71M6.35 6.35l-.71-.71M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" /></svg>
                </button>
            @endguest
            @auth
                <a href="{{ route('cart.index') }}" class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 transition hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" aria-label="Restaurant cart{{ $restaurantCartCount ? ': ' . $restaurantCartCount . ' items' : '' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13 5.4 5M7 13l-1.1 5.5A1 1 0 0 0 6.9 20h10.2M17 20a1 1 0 1 0 0 2 1 1 0 0 0-2Zm-10 0a1 1 0 1 0 0 2 1 1 0 0 0-2Z" /></svg>
                    @if ($restaurantCartCount > 0)<span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-600 px-1 text-xs font-bold text-white">{{ $restaurantCartCount > 99 ? '99+' : $restaurantCartCount }}</span>@endif
                </a>
                <div class="relative">
                    <button type="button" @click="accountOpen = !accountOpen" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium hover:bg-gray-100">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">{{ strtoupper(substr(auth()->user()?->first_name ?? 'G', 0, 1)) }}</span>
                        <span class="dark:text-slate-100">{{ auth()->user()?->name }}</span>
                    </button>
                    <div x-show="accountOpen" x-cloak @click.outside="accountOpen = false" class="absolute right-0 mt-2 w-48 overflow-hidden rounded-xl border bg-white py-1 text-gray-700 shadow-lg dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <a href="/dashboard" class="block px-4 py-2 text-sm hover:bg-gray-50">Dashboard</a>
                        <a href="/payments" class="block px-4 py-2 text-sm hover:bg-gray-50">Payments</a>
                        <a href="/profile" class="block px-4 py-2 text-sm hover:bg-gray-50">Profile</a>
                        <button type="button" @click="toggleTheme()" class="flex w-full items-center justify-between px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-slate-800" :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"><span>Appearance</span><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-amber-200"><svg x-show="!dark" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36-6.36-.71.71M6.35 17.65l-.71.71m12.72 0-.71-.71M6.35 6.35l-.71-.71M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg><svg x-show="dark" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" /></svg></span></button>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Logout</button></form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-blue-600">Login</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Sign Up</a>
            @endauth
        </div>

        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="guest-mobile-menu" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 lg:hidden">
            <span class="sr-only">Toggle navigation</span>
            <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <div id="guest-mobile-menu" x-show="open" x-cloak x-transition class="border-t border-gray-200 bg-white lg:hidden">
        <div class="space-y-1 px-4 py-3">
            <div class="rounded-lg">
                <button type="button" @click="mobileRoomsOpen = !mobileRoomsOpen" class="flex w-full items-center justify-between px-3 py-3 text-sm font-medium hover:bg-gray-50">Rooms <span aria-hidden="true">⌄</span></button>
                <div x-show="mobileRoomsOpen" x-cloak class="space-y-1 pb-2 pl-6"><a href="/rooms" class="block rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Browse Rooms</a></div>
            </div>
            <div class="rounded-lg">
                <button type="button" @click="mobileRestaurantOpen = !mobileRestaurantOpen" class="flex w-full items-center justify-between px-3 py-3 text-sm font-medium hover:bg-gray-50">Restaurant <span aria-hidden="true">⌄</span></button>
                <div x-show="mobileRestaurantOpen" x-cloak class="space-y-1 pb-2 pl-6 text-sm text-gray-600">
                    <a href="{{ route('restaurant') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-50">Restaurant Home</a><a href="{{ route('restaurant.menu') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-50">Menu</a><a href="{{ route('restaurant.tables') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-50">Restaurant Tables</a><a href="{{ route('restaurant.gallery') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-50">Gallery</a><a href="{{ route('restaurant.reserve') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-50">Reserve a Table</a><a href="{{ route('cart.index') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-50">Food Cart ({{ count(session('cart', [])) }})</a>
                </div>
            </div>
            <div class="rounded-lg">
                <button type="button" @click="mobileConferenceOpen = !mobileConferenceOpen" class="flex w-full items-center justify-between px-3 py-3 text-sm font-medium hover:bg-gray-50">Conferences <span aria-hidden="true">⌄</span></button>
                <div x-show="mobileConferenceOpen" x-cloak class="space-y-1 pb-2 pl-6"><a href="/conference-rooms" class="block rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Conference Rooms</a></div>
            </div>
            <a href="/contact" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Contact</a>
            <div class="border-t pt-2">
                @guest
                    <button type="button" @click="toggleTheme()" class="flex min-h-11 w-full items-center justify-between rounded-lg px-3 py-3 text-left text-sm font-medium hover:bg-gray-50 dark:hover:bg-slate-800" :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"><span>Appearance</span><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-amber-200"><svg x-show="!dark" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36-6.36-.71.71M6.35 17.65l-.71.71m12.72 0-.71-.71M6.35 6.35l-.71-.71M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg><svg x-show="dark" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" /></svg></span></button>
                @endguest
                @auth
                    <a href="/dashboard" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Dashboard</a>
                    <a href="{{ route('cart.index') }}" class="flex min-h-11 items-center justify-between rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50"><span>Restaurant cart</span><span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">{{ $restaurantCartCount }}</span></a>
                    <a href="/profile" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Profile</a>
                    <button type="button" @click="toggleTheme()" class="flex min-h-11 w-full items-center justify-between rounded-lg px-3 py-3 text-left text-sm font-medium hover:bg-gray-50" :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"><span>Appearance</span><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-amber-200"><svg x-show="!dark" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36-6.36-.71.71M6.35 17.65l-.71.71m12.72 0-.71-.71M6.35 6.35l-.71-.71M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg><svg x-show="dark" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" /></svg></span></button>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="block w-full rounded-lg px-3 py-3 text-left text-sm font-medium text-red-600 hover:bg-red-50">Logout</button></form>
                @else
                    <div class="grid grid-cols-2 gap-3 px-3 py-2">
                        <a href="{{ route('login') }}" class="rounded-lg border px-3 py-2 text-center text-sm font-semibold">Login</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-blue-600 px-3 py-2 text-center text-sm font-semibold text-white">Sign Up</a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</nav>
