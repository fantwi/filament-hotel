<?php

use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->get('/dashboard', function () {

    $user = auth()->user();

    if ($user->isStaff()) {
        return redirect('/admin');
    }

    $guest = $user?->guest;

    if (! $guest) {
        return view('dashboard', [
            'hotelBookings' => collect(),
            'conferenceBookings' => collect(),
            'restaurantReservations' => collect(),
            'restaurantOrders' => collect(),
            'upcomingHotelBookings' => collect(),
            'pastHotelBookings' => collect(),
            'upcomingConferenceBookings' => collect(),
            'pastConferenceBookings' => collect(),
            'upcomingRestaurantReservations' => collect(),
            'pastRestaurantReservations' => collect(),
            'totalBookings' => 0,
            'totalConfirmedBookings' => 0,
            'totalRestaurantReservations' => 0,
            'totalRestaurantOrders' => 0,
            'totalSpent' => 0,
            'outstandingBalance' => 0,
        ]);
    }

    Booking::where(
        'payment_status',
        'pending'
    )
        ->where(
            'hold_until',
            '<',
            now()
        )
        ->update([

            'hold_status' => 'expired',

            'payment_status' => 'expired',

            'hold_until' => null,

        ]);

    $hotelBookings = collect();
    $conferenceBookings = collect();
    $restaurantReservations = collect();
    $restaurantOrders = collect();

    if ($guest) {
        $hotelBookings = Booking::with(['room.roomType', 'payments'])
            ->where('guest_id', $guest->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function ($query) {
                $query
                    ->whereNull('hold_status')
                    ->orWhere('hold_status', '!=', 'expired');
            })
            ->latest()
            ->get();
    }

    if ($guest) {
        $conferenceBookings = ConferenceBooking::with('room')
            ->where('guest_id', $guest->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('payment_status', '!=', 'expired')
            ->latest()
            ->get();
    }

    if ($guest) {
        $restaurantReservations = RestaurantReservation::with([
            'restaurant',
            'table',
            'payments',
        ])
            ->where('guest_id', $guest->id)
            ->latest()
            ->get();

        $restaurantOrders = RestaurantOrder::with([
            'items.menuItem',
            'payments',
            'reservation.table',
        ])
            ->where('guest_id', $guest->id)
            ->latest()
            ->get();
    }

    $totalRestaurantReservations = $restaurantReservations->count();
    $totalRestaurantOrders = $restaurantOrders->count();

    $totalBookings = $hotelBookings->count() + $conferenceBookings->count() + $restaurantReservations->count();

    $totalConfirmedBookings =

        $hotelBookings->where('status', 'confirmed')->count()
        +
        $conferenceBookings->where('status', 'confirmed')->count()
        +
        $restaurantReservations->where('status', 'confirmed')->count();

    $totalSpent =
        $hotelBookings->where('payment_status', 'paid')->sum('total_price')
        +
        $conferenceBookings->where('payment_status', 'paid')->sum('total_price')
        +
        $restaurantReservations->where('payment_status', 'completed')->sum('reservation_fee')
        +
        $restaurantOrders->where('payment_status', 'completed')->sum('total');

    $outstandingBalance =
        $hotelBookings->filter(function ($booking) {
            return $booking->payment_status === 'pending' && $booking->hold_status !== 'expired';
        })->sum('total_price')
        +
        $conferenceBookings->filter(function ($booking) {
            return $booking->payment_status === 'pending';
        })->sum('total_price')
        +
        $restaurantReservations->filter(function ($reservation) {
            return $reservation->payment_status === 'pending'
                && $reservation->hold_status !== 'expired';
        })->sum('reservation_fee')
        +
        $restaurantOrders->filter(function ($order) {
            return $order->payment_status === 'pending'
                && $order->status !== 'cancelled';
        })->sum('total');

    $upcomingHotelBookings = $hotelBookings->filter(function ($booking) {
        return $booking->check_in >= now();
    });

    $pastHotelBookings = $hotelBookings->filter(function ($booking) {
        return $booking->check_out < now();
    });

    $upcomingConferenceBookings = $conferenceBookings->filter(function ($booking) {
        return $booking->check_in >= now();
    });

    $pastConferenceBookings = $conferenceBookings->filter(function ($booking) {
        return $booking->check_out < now();
    });

    $upcomingRestaurantReservations =
        $restaurantReservations->filter(function ($reservation) {
            return $reservation->reservation_date >= today();
        });

    $pastRestaurantReservations =
        $restaurantReservations->filter(function ($reservation) {
            return $reservation->reservation_date < today();
        });

    return view(
        'dashboard',
        compact('hotelBookings', 'conferenceBookings', 'restaurantReservations', 'restaurantOrders', 'upcomingHotelBookings',
            'pastHotelBookings', 'upcomingConferenceBookings', 'pastConferenceBookings', 'upcomingRestaurantReservations', 'pastRestaurantReservations',
            'totalBookings', 'totalConfirmedBookings', 'totalSpent', 'outstandingBalance', 'totalRestaurantReservations', 'totalRestaurantOrders'));

})->name('dashboard');

Route::get('/invoice/{booking}', function ($bookingId) {

    $booking = Booking::findOrFail($bookingId);

    if ($booking->guest_id !== auth()->user()->guest->id) {
        abort(403);
    }

    return response()->file(
        storage_path('app/public/invoices/invoice-booking-'.$booking->id.'.pdf')
    );

})->middleware('auth');

Route::get(
    '/rooms/{roomType}/calendar',
    function (RoomType $roomType) {

        abort_unless($roomType->is_published, 404);

        $rooms =
            Room::where(
                'room_type_id',
                $roomType->id
            )->get();

        $events = [];

        $totalRooms =
            $rooms->count();

        $startDate = today();

        $endDate =
            now()->addMonths(3)->endOfMonth();

        $period =
            CarbonPeriod::create(
                $startDate,
                $endDate
            );

        foreach ($period as $date) {

            $bookedCount =
                Booking::whereHas(
                    'room',
                    function ($query) use ($roomType) {

                        $query->where(
                            'room_type_id',
                            $roomType->id
                        );

                    }
                )
                    ->whereNotIn('status', ['cancelled', 'no_show'])
                    ->where(function ($query) {
                        $query->whereNull('hold_status')
                            ->orWhere('hold_status', '!=', 'expired');
                    })
                    ->whereDate(
                        'check_in',
                        '<=',
                        $date
                    )
                    ->whereDate(
                        'check_out',
                        '>',
                        $date
                    )
                    ->count();

            $available =
                $totalRooms - $bookedCount;

            if ($available <= 0) {

                $color = '#ef4444';

                $title = 'Fully Booked';

            } elseif ($available <= 2) {

                $color = '#facc15';

                $title =
                    "{$available} Rooms Left";

            } else {

                $color = '#22c55e';

                $title =
                    "{$available} Rooms Available";

            }

            $events[] = [

                'title' => $title,

                'start' => $date->format('Y-m-d'),

                'allDay' => true,

                'color' => $color,

            ];
        }

        return view(
            'rooms.calendar',
            compact(
                'roomType',
                'events',
                'rooms'
            )
        );

    })->name('rooms.calendar');

Route::middleware('auth')
    ->get(

        '/profile',

        function () {

            $user =
                auth()
                    ->user();

            $guest =
                $user
                    ?->guest;

            $hotelBookings =

                Booking::where(
                    'guest_id',
                    $guest?->id
                )
                    ->latest()
                    ->get();

            $conferenceBookings =

                ConferenceBooking::with(
                    'room'
                )
                    ->where(
                        'guest_id',
                        $guest?->id
                    )
                    ->latest()
                    ->get();

            $payments =

                Payment::where(
                    'guest_id',
                    $guest?->id
                )
                    ->latest()
                    ->get();

            return view(

                'profile',

                compact(

                    'user',

                    'guest',

                    'hotelBookings',

                    'conferenceBookings',

                    'payments'

                )
            );

        }
    )
    ->name('profile');

Route::post(

    '/profile/update',

    function (
        Request $request
    ) {

        $request->validate([

            'first_name' => 'required|string|max:255',

            'last_name' => 'required|string|max:255',

            'email' => 'required|string|email|max:255|unique:users,email,'.auth()->id(),

            'phone_number' => 'nullable|string|max:255',

            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

        ]);

        $user =
            auth()
                ->user();

        $guest =
            Guest::where(
                'user_id',
                $user->id
            )->first();

        if (
            $request->hasFile(
                'profile_photo'
            )
        ) {

            $file =

                $request->file(
                    'profile_photo'
                );

            $path =

                $file->store(

                    'profile_photos',

                    'public'

                );

            $user->profile_photo =
                $path;

            if ($guest) {

                $guest->profile_photo =
                    $path;
            }
        }

        $phoneNumber =

            $request->phone_number;

        $emailChanged =
            $user->email !== $request->email;

        $user->first_name = $request->first_name;

        $user->last_name = $request->last_name;

        $user->email = $request->email;

        if ($emailChanged) {

            $user->email_verified_at = null;
        }

        $user->phone_number =
            $phoneNumber;

        if ($guest) {

            $guest->first_name = $request->first_name;

            $guest->last_name = $request->last_name;

            $guest->email = $request->email;

            $guest->phone_number =
                $phoneNumber;
        }

        $user->save();

        if ($guest) {

            $guest->save();
        }

        return back()

            ->with(

                'success',

                'Profile updated successfully.'

            );
    }

)
    ->middleware('auth')
    ->name('profile.update');

Route::post(

    '/profile/password',

    function (
        Request $request
    ) {

        $request->validate([

            'current_password' => 'required',

            'password' => 'required|min:8|confirmed',

        ]);

        $user =
            auth()
                ->user();

        if (

            ! Hash::check(

                $request
                    ->current_password,

                $user
                    ->password

            )

        ) {

            return back()

                ->withErrors([

                    'current_password' => 'Current password is incorrect.',

                ]);
        }

        $user->password =

            bcrypt(
                $request
                    ->password
            );

        $user->save();

        return back()

            ->with(

                'success',

                'Password updated successfully.'

            );
    }

)
    ->middleware('auth')
    ->name(
        'profile.password'
    );

Route::middleware('auth')->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/calendar-events', [BookingController::class, 'calendarEvents'])
            ->name('calendar-events');
        Route::post('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule'])
            ->name('bookings.reschedule');
        Route::post('/bookings/{booking}/timeline-update', [BookingController::class, 'timelineUpdate'])
            ->name('bookings.timeline-update');
    });

    Route::get('/payments', function () {

        $user = auth()->user();

        if (! $user) {

            return redirect('/login');
        }

        $guest = $user->guest;

        if (! $guest) {

            return redirect('/dashboard');
        }

        Booking::where(
            'payment_status',
            'pending'
        )
            ->where(
                'hold_until',
                '<',
                now()
            )
            ->update([

                'hold_status' => 'expired',

                'payment_status' => 'expired',

            ]);

        $bookings =
            Booking::with([
                'room.roomType',
            ])
                ->where(
                    'guest_id',
                    $guest->id
                )
                ->where(
                    'payment_status',
                    'pending'
                )
                ->where(
                    'hold_status',
                    '!=',
                    'expired'
                )
                ->where(function ($query) {
                    $query
                        ->whereNull('hold_until')
                        ->orWhere(
                            'hold_until',
                            '>',
                            now()
                        );
                })
                ->latest()
                ->get();

        return view(
            'payments.index',
            compact('bookings')
        );

    })->middleware('auth')
        ->name('payments.index');

});
