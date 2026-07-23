<nav class="flex justify-between items-center px-6 py-4 bg-white shadow">

    <a href="/" class="text-xl font-bold">
        🏨 My Hotel
    </a>

    <div class="flex gap-4 items-center">

        <a href="/rooms" class="text-gray-600">
            Rooms
        </a>

        <a href="/conference-rooms" class="text-gray-600">
            Conference Rooms
        </a>

        <a href="/restaurant" class="text-gray-600">
            Restaurant
        </a>

        <a href="{{ route('restaurant.menu') }}" class="text-gray-600 hover:text-blue-600">
            Menu
        </a>

        <a href="{{ route('cart.index') }}" class="text-gray-600 hover:text-blue-600">
            Cart ({{ count(session('cart', [])) }})
        </a>

        <a href="/contact" class="text-gray-600">
            Contact
        </a>

        @auth
            <!-- <a href="/dashboard"
               class="bg-blue-600 text-white px-4 py-2 rounded">
               Dashboard
            </a> -->

            <!--<form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-red-600">
                    Logout
                </button>
            </form>-->
        @else
            <a href="{{ route('login') }}">
                Login
            </a>

            <a href="{{ route('register') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded">
               Sign Up
            </a>
        @endauth

        <!-- @auth

            <div
                class="flex items-center gap-3"
            >

                <div
                    class="w-10 h-10 rounded-full
                    bg-blue-100
                    flex items-center
                    justify-center
                    font-bold
                    text-blue-700"
                >

                    {{ strtoupper(
                        substr(
                            auth()->user()
                                ?->first_name ?? 'G',
                            0,
                            1
                        )
                    ) }}

                </div>

                <div>

                    <p
                        class="font-semibold text-sm"
                    >
                        {{ auth()->user()
                            ?->name }}
                    </p>

                    <p
                        class="text-xs text-gray-500"
                    >
                        // Guest Account 
                        <div
                            x-data="{ open: false }"
                            class="relative"
                        >

                            <button
                                @click="open = !open"
                                class="text-xs text-gray-500"
                            >

                                My Account

                            </button>

                            <div
                                x-show="open"
                                class="absolute right-0 mt-2 w-48 bg-white shadow rounded-xl p-3"
                            >

                                <a
                                    href="/dashboard"
                                    class="block py-2"
                                >
                                    Dashboard
                                </a>

                                <a
                                    href="/payments"
                                    class="block py-2"
                                >
                                    Payments
                                </a>

                                <a
                                    href="/profile"
                                    class="block py-2"
                                >
                                    Profile
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route('logout') }}"
                                >
                                    @csrf

                                    <button
                                        class="text-red-600"
                                    >
                                        Logout
                                    </button>
                                </form>

                            </div>

                        </div>
                    </p>

                </div>

            </div>

        @endauth -->

        @auth

            <div
                x-data="{ open: false }"

                class="relative"
            >

                <!-- CLICKABLE ACCOUNT -->
                <button

                    @click="
                        open = !open
                    "

                    class="flex
                    items-center
                    gap-3
                    hover:bg-gray-100
                    rounded-xl
                    px-3 py-2
                    transition"

                >

                    <!-- PROFILE PHOTO -->
                    @if(
                        auth()->user()
                        ?->profile_photo
                    )

                        <img

                            src="{{
                                asset(
                                    'storage/' .
                                    auth()->user()
                                    ->profile_photo
                                )
                            }}"

                            class="w-10
                            h-10
                            rounded-full
                            object-cover
                            border"
                        >

                    @else

                        <!-- AVATAR -->
                        <div
                            class="w-10
                            h-10
                            rounded-full
                            bg-blue-100
                            flex
                            items-center
                            justify-center
                            font-bold
                            text-blue-700"
                        >

                            {{

                                strtoupper(

                                    substr(

                                        auth()->user()
                                        ?->first_name
                                        ?? 'G',

                                        0,

                                        1

                                    )

                                )

                            }}

                        </div>

                    @endif


                    <!-- NAME + ACCOUNT -->
                    <div class="text-left">

                        <p
                            class="font-semibold
                            text-sm"
                        >

                            {{
                                auth()->user()
                                ?->name
                            }}

                        </p>

                        <p
                            class="text-xs
                            text-gray-500"
                        >

                            My Account

                        </p>

                    </div>


                    <!-- DROPDOWN ARROW -->
                    <svg

                        class="w-4 h-4
                        text-gray-500"

                        fill="none"

                        stroke="currentColor"

                        viewBox="0 0 24 24"
                    >

                        <path

                            stroke-linecap="round"

                            stroke-linejoin="round"

                            stroke-width="2"

                            d="M19 9l-7 7-7-7"

                        />

                    </svg>

                </button>


                <!-- DROPDOWN MENU -->
                <div

                    x-show="open"

                    @click.away="
                        open = false
                    "

                    x-transition

                    class="absolute
                    right-0
                    mt-3
                    w-56
                    bg-white
                    rounded-2xl
                    shadow-xl
                    border
                    overflow-hidden
                    z-50"

                >

                    <a

                        href="/dashboard"

                        class="block
                        px-5 py-3
                        hover:bg-gray-100"

                    >

                        Dashboard

                    </a>

                    <a

                        href="/payments"

                        class="block
                        px-5 py-3
                        hover:bg-gray-100"

                    >

                        Payments

                    </a>

                    <a

                        href="/profile"

                        class="block
                        px-5 py-3
                        hover:bg-gray-100"

                    >

                        Profile

                    </a>

                    <form

                        method="POST"

                        action="{{ route('logout') }}"
                    >

                        @csrf

                        <button

                            class="w-full
                            text-left
                            px-5 py-3
                            text-red-600
                            hover:bg-red-50"

                        >

                            Logout

                        </button>

                    </form>

                </div>

            </div>

        @endauth

    </div>

</nav>
