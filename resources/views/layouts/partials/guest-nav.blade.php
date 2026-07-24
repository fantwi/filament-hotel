<nav x-data="{ open: false, accountOpen: false }" class="fixed inset-x-0 top-0 z-50 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <a href="/" class="shrink-0 text-lg font-bold text-gray-900 sm:text-xl">My Hotel</a>

        <div class="hidden items-center gap-4 text-sm font-medium text-gray-600 lg:flex">
            <a href="/rooms" class="hover:text-blue-600">Rooms</a>
            <a href="/conference-rooms" class="hover:text-blue-600">Conference Rooms</a>
            <a href="/restaurant" class="hover:text-blue-600">Restaurant</a>
            <a href="{{ route('restaurant.menu') }}" class="hover:text-blue-600">Menu</a>
            <a href="{{ route('cart.index') }}" class="hover:text-blue-600">Cart ({{ count(session('cart', [])) }})</a>
            <a href="/contact" class="hover:text-blue-600">Contact</a>
        </div>

        <div class="hidden items-center gap-3 lg:flex">
            @auth
                <div class="relative">
                    <button type="button" @click="accountOpen = !accountOpen" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium hover:bg-gray-100">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">{{ strtoupper(substr(auth()->user()?->first_name ?? 'G', 0, 1)) }}</span>
                        <span>{{ auth()->user()?->name }}</span>
                    </button>
                    <div x-show="accountOpen" x-cloak @click.outside="accountOpen = false" class="absolute right-0 mt-2 w-48 overflow-hidden rounded-xl border bg-white py-1 shadow-lg">
                        <a href="/dashboard" class="block px-4 py-2 text-sm hover:bg-gray-50">Dashboard</a>
                        <a href="/payments" class="block px-4 py-2 text-sm hover:bg-gray-50">Payments</a>
                        <a href="/profile" class="block px-4 py-2 text-sm hover:bg-gray-50">Profile</a>
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
            <a href="/rooms" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Rooms</a>
            <a href="/conference-rooms" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Conference Rooms</a>
            <a href="/restaurant" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Restaurant</a>
            <a href="{{ route('restaurant.menu') }}" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Menu</a>
            <a href="{{ route('cart.index') }}" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Cart ({{ count(session('cart', [])) }})</a>
            <a href="/contact" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Contact</a>
            <div class="border-t pt-2">
                @auth
                    <a href="/dashboard" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Dashboard</a>
                    <a href="/profile" class="block rounded-lg px-3 py-3 text-sm font-medium hover:bg-gray-50">Profile</a>
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
