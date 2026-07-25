<footer class="border-t border-gray-800 bg-[#161b48e6] text-gray-300">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="/" class="text-xl font-bold text-white">My Hotel</a>
                <p class="mt-4 max-w-xs text-sm leading-6 text-gray-400">Comfortable stays, memorable dining, and flexible spaces for every occasion.</p>
                <p class="mt-5 text-sm text-gray-400">Cape Coast, Ghana</p>
                <a href="mailto:info@myhotel.com" class="mt-1 inline-block text-sm text-amber-300 hover:text-amber-200">info@myhotel.com</a>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Stay</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="/rooms" class="hover:text-white">Rooms</a></li>
                    <li><a href="/conference-rooms" class="hover:text-white">Conference Rooms</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white">Contact Us</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Restaurant</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('restaurant') }}" class="hover:text-white">Restaurant</a></li>
                    <li><a href="{{ route('restaurant.menu') }}" class="hover:text-white">Menu</a></li>
                    <li><a href="{{ route('restaurant.tables') }}" class="hover:text-white">Restaurant Tables</a></li>
                    <li><a href="{{ route('restaurant.gallery') }}" class="hover:text-white">Gallery</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Guest Services</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('cart.index') }}" class="hover:text-white">Food Cart</a></li>
                    @guest
                        <li><a href="{{ route('login') }}" class="hover:text-white">Guest Login</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white">Create Account</a></li>
                    @else
                        <li><a href="/dashboard" class="hover:text-white">My Dashboard</a></li>
                        <li><a href="/profile" class="hover:text-white">My Profile</a></li>
                    @endguest
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-3 border-t border-gray-800 pt-6 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} My Hotel. All rights reserved.</p>
            <p>Website information is current as of {{ now()->format('F Y') }}.</p>
        </div>
    </div>
</footer>
