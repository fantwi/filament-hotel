<x-guest-layout>
<div
    x-data="{ tab: 'profile' }"

    class="max-w-6xl
    mx-auto
    py-10"
>

    <div
        class="bg-white
        rounded-2xl
        shadow
        overflow-hidden"
    >

        {{-- HEADER --}}
        <div
            class="bg-blue-600
            text-white
            p-8"
        >

            <div
                class="flex
                items-center
                gap-6"
            >

                <!-- <img
                    src="{{
                        $guest->profile_photo

                        ?

                        asset(
                            'storage/' .
                            $guest->profile_photo
                        )

                        :

                        (

                            $user->profile_photo
                            
                            ? 

                            asset(
                                'storage/' .
                                $user->profile_photo
                            )

                            :

                            'https://ui-avatars.com/api/?name='
                            . urlencode(
                                $user->name
                            )
                        )
                    }}"
                    class="w-28
                    h-28
                    rounded-full
                    object-cover
                    border-4
                    border-white"
                > -->

                <img

                    src="{{

                        $guest?->profile_photo

                        ?

                        asset(
                            'storage/' .
                            $guest->profile_photo
                        )

                        :

                        (

                            $user->profile_photo

                            ?

                            asset(
                                'storage/' .
                                $user->profile_photo
                            )

                            :

                            'https://ui-avatars.com/api/?name=' .
                            urlencode(
                                $user->name
                            )

                        )

                    }}"

                    class="w-28
                    h-28
                    rounded-full
                    object-cover
                    border-4
                    border-white"
                />

                <div>

                    <h1
                        class="text-3xl
                        font-bold"
                    >

                        {{
                            $user->name
                        }}

                    </h1>

                    <p>
                        {{
                            $user->email
                        }}
                    </p>

                </div>

            </div>

        </div>

        {{-- TABS --}}
        <div
            class="flex
            border-b"
        >

            <button
                @click="
                    tab='profile'
                "

                class="px-6
                py-4"
            >
                Profile
            </button>

            <button
                @click="
                    tab='bookings'
                "

                class="px-6
                py-4"
            >
                Booking History
            </button>

            <button
                @click="
                    tab='payments'
                "

                class="px-6
                py-4"
            >
                Payment History
            </button>

            <button
                @click="
                    tab='security'
                "

                class="px-6
                py-4"
            >
                Security
            </button>

        </div>

        <div class="p-8">

            {{-- PROFILE --}}
            <div
                x-show="
                    tab==='profile'
                "
            >

                <form
                    method="POST"
                    action="{{ route('profile.update') }}"
                    enctype="multipart/form-data"
                    
                >

                    @csrf

                    <div
                        class="mb-4"
                    >

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="text"
                            name="phone_number"
                            value="{{
                                old(
                                    'phone_number',
                                    $user->phone_number
                                )
                            }}"
                            class="w-full
                            border
                            rounded-lg
                            p-3"
                        >

                    </div>

                    <div
                        class="mb-4"
                    >

                        <label>
                            Profile Picture
                        </label><br>

                        <input
                            type="file"
                            name="
                                profile_photo
                            "
                        >

                    </div>

                    <button
                        class="bg-blue-600
                        text-white
                        px-5 py-3
                        rounded-lg"
                    >

                        Update Profile

                    </button>

                </form>

            </div>

            {{-- BOOKINGS --}}
            <div
                x-show="
                    tab==='bookings'
                "
            >

                <h2
                    class="font-bold
                    text-xl
                    mb-4"
                >

                    Hotel Bookings

                </h2>

                @foreach(
                    $hotelBookings
                    as $booking
                )

                    <div
                        class="border
                        rounded-lg
                        p-4 mb-3"
                    >

                        <!-- {{
                            $booking->room->room_type
                        }}
                        
                        <br> -->
                        
                        Room 
                        {{
                            $booking->room_id
                        }}

                        —
                        GHS
                        {{
                            $booking->total_price
                        }}

                    </div>

                @endforeach

                <h2
                    class="font-bold
                    text-xl
                    mt-8 mb-4"
                >

                    Conference Bookings

                </h2>

                @foreach(
                    $conferenceBookings
                    as $booking
                )

                    <div
                        class="border
                        rounded-lg
                        p-4 mb-3"
                    >

                        {{
                            $booking->room?->name
                        }}

                        —
                        GHS
                        {{
                            $booking->total_price
                        }}

                    </div>

                @endforeach

            </div>

            {{-- PAYMENTS --}}
            <div
                x-show="
                    tab==='payments'
                "
            >

                @foreach(
                    $payments
                    as $payment
                )

                    <div
                        class="border
                        rounded-lg
                        p-4 mb-3"
                    >

                        GHS
                        {{
                            $payment
                            ->amount
                        }}

                        —

                        {{
                            $payment
                            ->method
                        }}

                    </div>

                @endforeach

            </div>

            {{-- SECURITY --}}
            <div
                x-show="
                    tab==='security'
                "
            >

                <!-- <form
                    method="POST"
                    action="{{ route(
                        'profile.password'
                    ) }}"
                >

                    @csrf

                    <input
                        type="password"
                        name="
                            current_password
                        "
                        placeholder="
                            Current Password
                        "
                        class="w-full
                        border
                        rounded-lg
                        p-3 mb-3"
                    >

                    <input
                        type="password"
                        name="
                            password
                        "
                        placeholder="
                            New Password
                        "
                        class="w-full
                        border
                        rounded-lg
                        p-3 mb-3"
                    >

                    <button
                        class="bg-red-600
                        text-white
                        px-5 py-3
                        rounded-lg"
                    >

                        Change Password

                    </button>

                </form> -->

                <form
                    method="POST"
                    action="{{
                        route(
                            'profile.password'
                        )
                    }}"
                >

                    @csrf

                    <input
                        type="password"

                        name="
                            current_password
                        "

                        placeholder="
                            Current Password
                        "

                        class="w-full
                        border
                        rounded-lg
                        p-3
                        mb-3"
                    >

                    <input
                        type="password"

                        name="
                            password
                        "

                        placeholder="
                            New Password
                        "

                        class="w-full
                        border
                        rounded-lg
                        p-3
                        mb-3"
                    >

                    <input
                        type="password"

                        name="
                            password_confirmation
                        "

                        placeholder="
                            Confirm Password
                        "

                        class="w-full
                        border
                        rounded-lg
                        p-3
                        mb-3"
                    >

                    <button
                        class="bg-red-600
                        text-white
                        px-5 py-3
                        rounded-lg"
                    >

                        Change Password

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>
</x-guest-layout>
