<?php

use App\Http\Controllers\BookingController;
// use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RestaurantReservationController;
use App\Http\Controllers\RestaurantCartController;
use App\Http\Controllers\RestaurantCheckoutController;
use App\Http\Controllers\RestaurantOrderPaymentController;
use App\Http\Controllers\RestaurantTableOrderController;
use App\Http\Controllers\RestaurantTableQrController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Guest;
use App\Models\ConferenceRoom;
use App\Models\ConferenceBooking;
use App\Models\ContactMessage;
use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Services\InvoiceService;
use App\Mail\RestaurantPaymentReceived;
use Carbon\CarbonPeriod;
use Barryvdh\DomPDF\Facade\Pdf;

Route::get('/', function () {
    $roomTypes = RoomType::query()->with('facilities')->take(3)->get();
    $conferenceRooms = ConferenceRoom::query()->where('is_available', true)->take(3)->get();
    $restaurant = Restaurant::first();

    return view('index', compact('roomTypes', 'conferenceRooms', 'restaurant'));
})->name('home');

// Route::get('/dashboard', function () {

//     $user = Auth::user();
//     $guest = $user->guest;

//     // $bookings = $guest?->bookings()
//     //     ->with(['room', 'payments'])
//     //     ->latest()
//     //     ->get();

//     $bookings = $guest
//         ? $guest->bookings()->with(['room', 'payments'])->latest()->get()
//         : collect();

//     return view('dashboard', compact('bookings'));
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/contact', function () {
        return view('contact');
    }
)->name('contact');

Route::post('/contact', function (Request $request) {
        $request->validate([
            'name' =>
                'required',
            'email' =>
                'required|email',
            'phone_number' =>
                'nullable',
            'subject' =>
                'required',
            'message' =>
                'required',
        ]);

        ContactMessage::create([
            'name' =>
                $request->name,
            'email' =>
                $request->email,
            'phone_number' =>
                $request->phone_number,
            'subject' =>
                $request->subject,
            'message' =>
                $request->message,
        ]);

        return back()->with('success', 'Message sent successfully.');
    }
);

Route::get('/conference-rooms', function () {
        // $rooms = ConferenceRoom::where('is_available', true)->get();
        
        $rooms = ConferenceRoom::with('facilities')->where('is_available', true)->get();

        return view('conference.index', compact('rooms'));
    })->name('conference.index');

Route::middleware('auth')->group(function () {

        Route::get('/conference-room/{room}/book',
            function (ConferenceRoom $room) {
                return view('conference.book', compact('room'));
            })->name('conference.book');

        Route::post('/conference-room/book',
            function (Request $request) {
                $request->validate([
                    'conference_room_id' =>
                        'required',
                    'booking_date' =>
                        'required|date',
                    'start_time' =>
                        'required',
                    'end_time' =>
                        'required',
                    'attendees' =>
                        'required|integer|min:1',
                ]);

                $guest = auth()->user()->guest;

                $room = ConferenceRoom::findOrFail(
                        $request
                        ->conference_room_id
                    );

                if (! $room->is_available) {
                    return back()->withInput()->withErrors(['conference_room_id' => 'This conference room is unavailable.']);
                }

                // Calculate hours

                $start =
                    \Carbon\Carbon::parse(
                        $request->start_time
                    );

                $end =
                    \Carbon\Carbon::parse(
                        $request->end_time
                    );

                // prevent negative duration
                if ($end <= $start) {

                    return back()

                        ->withErrors([

                            'end_time' =>

                                'End time must be after start time.'

                        ])

                        ->withInput();
                }

                $overlaps = ConferenceBooking::query()
                    ->where('conference_room_id', $room->id)
                    ->whereDate('booking_date', $request->booking_date)
                    ->where(function ($query) {
                        $query->whereIn('status', ['confirmed', 'checked_in'])
                            ->orWhere(function ($pending) {
                                $pending->where('status', 'pending')->where('hold_until', '>', now());
                            });
                    })
                    ->where('start_time', '<', $request->end_time)
                    ->where('end_time', '>', $request->start_time)
                    ->exists();

                if ($overlaps) {
                    return back()->withInput()->withErrors(['start_time' => 'This conference room is already booked for the selected time slot.']);
                }

                $hours =
                    $start->diffInHours(
                        $end
                    );

                $total =
                    $hours *
                    $room->price_per_hour;

                // $hours = \Carbon\Carbon::parse(
                //         $request
                //         ->start_time
                //     )
                //     ->diffInHours(

                //         \Carbon\Carbon::parse(
                //             $request
                //             ->end_time
                //         )
                //     );

                $total = $hours * $room->price_per_hour;

                $booking = ConferenceBooking::create([
                    'conference_room_id' => $room->id,
                    'guest_id' => $guest->id,
                    'booking_date' => $request->booking_date,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'attendees' => $request->attendees,
                    'special_requests' => $request->special_requests,
                    'total_price' => $total,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'hold_until' => now()->addMinutes(15),
                ]);

                if ($booking->payment_status === 'pending'
                    &&
                    $booking->hold_until?->isPast()) {
                    $booking->update([
                        'status' => 'expired',
                        'payment_status' => 'expired',
                    ]);

                    return redirect()->route('conference.expired');
                }

                return redirect()->route('conference.payment', $booking->id);
                    //->with('success', 'Conference room booked successfully.');
            })->name('conference.booking.store');
    });

Route::get('/conference-booking/{booking}/payment',
    function (ConferenceBooking $booking) {

        return view('conference.payment', compact('booking'));
    })->name('conference.payment');

// Route::post('/conference-booking/{booking}/pay',
//     function (ConferenceBooking $booking) {
//         // demo payment
//         $booking->update([
//             'payment_status' => 'paid',
//             'status' => 'confirmed',
//             'hold_until' => null,
//         ]);

//         Payment::create([
//             'booking_id' => null,
//             'conference_booking_id' => $booking->id,
//             'guest_id' => $booking->guest_id,
//             'amount' => abs($booking->total_price),
//             'method' => 'paystack',
//             'payment_status' => 'completed',
//         ]);

//         return redirect()
//             ->route('dashboard')
//             ->with('success', 'Conference room booked successfully.');
//     })->name('conference.pay');

Route::post('/conference-booking/{booking}/pay',
    function (
        ConferenceBooking $booking
    ) {

        if (!$booking || $booking->payment_status === 'paid') {
            return back()
                ->with('error', 'Booking already paid.');
        }

        $response = Http::withToken(config('services.paystack.secretKey'))
            ->post(
                'https://api.paystack.co/transaction/initialize',
                [
                    'email' => auth()->user()->email,

                    'amount' => $booking->total_price * 100,

                    'reference' => 'CONF_' . uniqid(),

                    'callback_url' =>
                        route('conference.verify', $booking->id),
                ]
            )
            ->json();

        if (!isset($response['data']['authorization_url'])) {
            return back()
                ->with('error', 'Payment initialization failed.');
        }

        session([
            'conference_payment_reference' =>
                $response['data']['reference']
        ]);

        return redirect(
            $response
                ['data']
                ['authorization_url']
        );
})->name('conference.pay');

Route::get('/conference-booking/{booking}/verify',

    function (ConferenceBooking $booking) {

        $reference = request('reference');

        if (!$reference) {
            return redirect()
                ->route(
                    'conference.payment',
                    $booking->id
                )
                ->with(
                    'error',
                    'Missing payment reference.'
                );
        }

        $response =
            Http::withToken(
                config(
                    'services.paystack.secretKey'
                )
            )
            ->get(
                "https://api.paystack.co/transaction/verify/{$reference}"
            )
            ->json();

        if (!isset($response['data']) || $response['data']['status'] !== 'success') {
            return redirect()
                ->route(
                    'conference.payment',
                    $booking->id
                )
                ->with(
                    'error',
                    'Payment verification failed.'
                );
        }

        // Update booking
        $booking->update([
            'payment_status' =>
                'paid',
            'status' =>
                'confirmed',
            'hold_until' =>
                null,
        ]);

        // Save payment
        Payment::create([
            'booking_id' =>
                null,
            'conference_booking_id' =>
                $booking->id,
            'guest_id' =>
                $booking->guest_id,
            'amount' =>
                $booking->total_price,
            'method' =>
                'paystack',
            'status' =>
                'completed',
        ]);

        return redirect()
            ->route(
                'dashboard'
            )
            ->with(
                'success',
                'Conference booking confirmed.'
            );
    })->name('conference.verify');

Route::get('/conference-booking/expired',
    function () {
        return view('conference.expired');
    })->name('conference.expired');

Route::post(

    '/conference-booking/{booking}/cancel',

    function (
        App\Models\ConferenceBooking $booking
    ) {

        if (

            $booking
                ->payment_status
            === 'paid'

        ) {

            return back()

                ->with(
                    'error',
                    'Paid bookings cannot be cancelled.'
                );
        }

        $booking->update([

            'status' =>
                'cancelled',

            'payment_status' =>
                $booking->payment_status
                === 'paid'

                ? 'paid'

                : 'failed',

        ]);

        return back()

            ->with(
                'success',
                'Conference booking cancelled.'
            );
    }

)->name(
    'conference.cancel'
);

Route::get(

    '/conference-booking/{booking}/invoice',

    function (
        App\Models\ConferenceBooking $booking
    ) {

        $pdf =
            Pdf::loadView(

                'invoice.conference',

                compact(
                    'booking'
                )
            );

        return $pdf
            ->download(

                'conference_invoice.pdf'
            );
    }

)->name(
    'conference.invoice'
);

// Route::get('/restaurant', function () {
//     return view('restaurant.index');
// })->name('restaurant');

Route::get('/restaurant', function () {

    $restaurant = Restaurant::with([
        'tables' => fn ($query) => $query->orderBy('table_number')->limit(3),
        'facilities',
    ])->first();
    $categories = MenuCategory::query()
        ->with(['menuItems' => fn ($query) => $query
            ->where('is_available', true)
            ->orderBy('sort_order')])
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get();
    $featuredItems = MenuItem::query()
        ->where('is_available', true)
        ->where('is_featured', true)
        ->orderBy('sort_order')
        ->limit(4)
        ->get();

    return view(
        'restaurant.index',
        compact('restaurant', 'categories', 'featuredItems')
    );

})->name('restaurant');

Route::get('/restaurant/tables', function () {
    $restaurant = Restaurant::with([
        'tables' => fn ($query) => $query->orderBy('table_number'),
    ])->first();

    return view('restaurant.tables', compact('restaurant'));
})->name('restaurant.tables');

Route::get('/restaurant/gallery', function () {
    $restaurant = Restaurant::first();

    return view('restaurant.gallery', compact('restaurant'));
})->name('restaurant.gallery');

Route::get('/restaurant/menu', function () {
    $restaurant = Restaurant::first();

    $categories = MenuCategory::query()
        ->with(['menuItems' => fn ($query) => $query
            ->where('is_available', true)
            ->orderBy('sort_order')])
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get();

    $featuredItems = MenuItem::query()
        ->where('is_available', true)
        ->where('is_featured', true)
        ->orderBy('sort_order')
        ->limit(4)
        ->get();

    return view('restaurant.menu', compact('restaurant', 'categories', 'featuredItems'));
})->name('restaurant.menu');

Route::get('/table/{table:qr_token}/menu', [RestaurantTableOrderController::class, 'menu'])
    ->middleware('throttle:60,1')
    ->name('restaurant.table.menu');
Route::post('/restaurant/table-session/leave', [RestaurantTableOrderController::class, 'leaveTable'])
    ->name('restaurant.table.leave');
Route::middleware('auth')->get('/admin/restaurant-tables/{table}/qr', [RestaurantTableQrController::class, 'print'])
    ->name('restaurant.tables.qr.print');

Route::post('/cart/add/{item}', [RestaurantCartController::class, 'add'])->name('cart.add');
Route::get('/cart', [RestaurantCartController::class, 'index'])->name('cart.index');
Route::post('/cart/update/{item}', [RestaurantCartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{item}', [RestaurantCartController::class, 'remove'])->name('cart.remove');
Route::get('/restaurant/checkout', [RestaurantCheckoutController::class, 'index'])->name('restaurant.checkout');
Route::post('/restaurant/checkout', [RestaurantCheckoutController::class, 'store'])->name('restaurant.checkout.store');
Route::get('/restaurant/orders/{order}/confirmation', [RestaurantOrderPaymentController::class, 'confirmation'])->name('restaurant.orders.confirmation');
Route::post('/restaurant/orders/{order}/pay', [RestaurantOrderPaymentController::class, 'initialize'])->name('restaurant.orders.pay');
Route::get('/restaurant/orders/payment/callback', [RestaurantOrderPaymentController::class, 'callback'])->name('restaurant.orders.payment.callback');

Route::get(
    '/restaurant/reserve',
    [RestaurantReservationController::class, 'create']
)->name('restaurant.reserve');

Route::get(
    '/restaurant/reservations/{reservation}/print',
    function (RestaurantReservation $reservation) {
        abort_unless(
            auth()->user()?->hasAnyRole(['admin', 'receptionist']),
            403
        );

        return view('restaurant.print', compact('reservation'));
    }
)->middleware('auth')->name('restaurant.reservations.print');

Route::post(
    '/restaurant/reserve',
    [RestaurantReservationController::class, 'store']
)->name('restaurant.reserve.store');

Route::get(

    '/restaurant/reservations/{reservation}/payment',

    function (

        RestaurantReservation $reservation

    ) {

        if (

            $reservation->hold_status == 'expired'

        ) {

            abort(403);

        }

        if (

            $reservation->hold_until

            &&

            now()->greaterThan(

                $reservation->hold_until

            )

        ) {

            $reservation->update([

                'hold_status' => 'expired',

                'status' => 'cancelled',

                'payment_status' => 'cancelled',

            ]);

            abort(403);

        }

        $reservation->table->update([
                'status' => 'available',
            ]);

        if(

    $reservation->hold_status == 'expired'

    ){

        return back()->with(

            'error',

            'Reservation expired.'

        );

    }

        return view(

            'restaurant.payment',

            compact('reservation')

        );

    }

)->name('restaurant.payment');

Route::post(
    '/restaurant/reservations/{reservation}/pay',
    function (RestaurantReservation $reservation) {

        if ($reservation->payment_status === 'completed') {

            return back()->with(
                'error',
                'Reservation has already been paid.'
            );
        }

        if ($reservation->hold_status === 'expired') {

            return back()->with(
                'error',
                'Reservation has expired.'
            );
        }

        $response = Http::withToken(
            config('services.paystack.secretKey')
        )
        ->post(
            'https://api.paystack.co/transaction/initialize',
            [

                'email' => auth()->user()->email,

                'amount' => $reservation->reservation_fee * 100,

                'reference' => 'REST_' . uniqid(),

                'callback_url' => route(
                    'restaurant.verify',
                    $reservation
                ),

            ]
        )
        ->json();

        if (
            ! isset(
                $response['data']['authorization_url']
            )
        ) {

            return back()->with(
                'error',
                'Unable to initialize payment.'
            );

        }

        return redirect(
            $response['data']['authorization_url']
        );

    }
)->middleware('auth')
 ->name('restaurant.pay');

Route::get(
    '/restaurant/reservations/{reservation}/verify',
    function (RestaurantReservation $reservation) {

        $reference = request('reference');

        if (! $reference) {

            return redirect()
                ->route(
                    'restaurant.payment',
                    $reservation
                )
                ->with(
                    'error',
                    'Missing payment reference.'
                );
        }

        $response = Http::withToken(
            config('services.paystack.secretKey')
        )
        ->get(
            "https://api.paystack.co/transaction/verify/{$reference}"
        )
        ->json();

        if (
            ! isset($response['data']) ||
            $response['data']['status'] !== 'success'
        ) {

            return redirect()
                ->route(
                    'restaurant.payment',
                    $reservation
                )
                ->with(
                    'error',
                    'Payment verification failed.'
                );
        }

        $reservation->update([

            'payment_status' => 'completed',

            'status' => 'confirmed',

            'hold_status' => 'confirmed',

            'hold_until' => null,

            'transaction_reference' => $reference,

        ]);

        activity()
            ->performedOn($reservation)
            ->causedBy(auth()->user())
            ->event('payment')
            ->log('Restaurant reservation paid.');

        Mail::to($reservation->guest_email)
            ->send(new RestaurantPaymentReceived($reservation));

        $reservation->table()->update([

            'status' => 'reserved',

        ]);

        Payment::create([

            'restaurant_reservation_id' => $reservation->id,

            'guest_id' => $reservation->guest_id,

            'amount' => $reservation->reservation_fee,

            'method' => 'paystack',

            'payment_status' => 'completed',

            'transaction_reference' => $reference,

        ]);

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'Restaurant reservation confirmed.'
            );

    }
)->middleware('auth')
 ->name('restaurant.verify');

Route::middleware('auth')->get('/dashboard', function () {

    $user = auth()->user();

    // block staff from guest dashboard
    if ($user->isStaff()) {
        return redirect('/admin');
    }

    $guest = $user?->guest;

    if (!$guest) {
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

    // expire unpaid hotel bookings
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

        'hold_status' =>
            'expired',

        'payment_status' =>
            'expired',

        'hold_until' =>
            null,

    ]);

    $hotelBookings = collect();
    $conferenceBookings = collect();
    $restaurantReservations = collect();
    $restaurantOrders = collect();

    // hotel bookings
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

    // conference bookings
    if ($guest) {
        $conferenceBookings = ConferenceBooking::with('room')
            ->where('guest_id', $guest->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('payment_status', '!=', 'expired')
            ->latest()
            ->get();
    }

    // restaurant reservations
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

    // RESTAURANT RESERVATIONS
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
        // HOTEL BOOKINGS
        $hotelBookings->where('payment_status', 'paid')->sum('total_price')
        +
        // CONFERENCE BOOKINGS
        $conferenceBookings->where('payment_status', 'paid')->sum('total_price')
        +
        // RESTAURANT RESERVATIONS
        $restaurantReservations->where('payment_status', 'completed')->sum('reservation_fee')
        +
        // RESTAURANT FOOD ORDERS
        $restaurantOrders->where('payment_status', 'completed')->sum('total');

    $outstandingBalance =
        // HOTEL BOOKINGS
        $hotelBookings->filter(function ($booking) {
                return $booking->payment_status === 'pending' && $booking->hold_status !== 'expired';
            })->sum('total_price')
        +
        // CONFERENCE BOOKINGS
        $conferenceBookings->filter(function ($booking) {
                return $booking->payment_status === 'pending';
            })->sum('total_price')
        +
        // RESTAURANT RESERVATIONS
        $restaurantReservations->filter(function ($reservation) {
                return $reservation->payment_status === 'pending'
                    && $reservation->hold_status !== 'expired';
            })->sum('reservation_fee')
        +
        // RESTAURANT FOOD ORDERS
        $restaurantOrders->filter(function ($order) {
                return $order->payment_status === 'pending'
                    && $order->status !== 'cancelled';
            })->sum('total');

    // $upcoming = $hotelBookings->where('check_in', '>=', now());
    // $past = $hotelBookings->where('check_out', '<', now());

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

    $booking = \App\Models\Booking::findOrFail($bookingId);

    // Only owner can access
    if ($booking->guest_id !== auth()->user()->guest->id) {
        abort(403);
    }

    return response()->file(
        storage_path('app/public/invoices/invoice-booking-'.$booking->id.'.pdf')
    );

})->middleware('auth');

Route::get(
    '/rooms/{roomType}/calendar',
    function (\App\Models\RoomType $roomType) {

        $rooms =
            Room::where(
                'room_type_id',
                $roomType->id
            )->get();

        // $events = [];

        // foreach ($rooms as $room) {

        //     $bookings =
        //         $room->bookings;

        //     foreach ($bookings as $booking) {

        //         $events[] = [

        //             'title' =>
        //                 'Booked',

        //             'start' =>
        //                 $booking->check_in,

        //             'end' =>
        //                 \Carbon\Carbon::parse(
        //                     $booking->check_out
        //                 )->addDay(),

        //             'color' =>
        //                 '#ef4444',

        //         ];
        //     }
        // }

        $events = [];

        $totalRooms =
            $rooms->count();

        $startDate =
            now()->startOfMonth();

        $endDate =
            now()->addMonths(3)->endOfMonth();

        $period =
            \Carbon\CarbonPeriod::create(
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
                ->whereDate(
                    'check_in',
                    '<=',
                    $date
                )
                ->whereDate(
                    'check_out',
                    '>=',
                    $date
                )
                ->count();

            $available =
                $totalRooms - $bookedCount;

            // FULLY BOOKED
            if ($available <= 0) {

                $color = '#ef4444';

                $title = 'Fully Booked';

            }

            // LIMITED
            elseif ($available <= 2) {

                $color = '#facc15';

                $title =
                    "{$available} Rooms Left";

            }

            // AVAILABLE
            else {

                $color = '#22c55e';

                $title =
                    "{$available} Rooms Available";

            }

            $events[] = [

                'title' => $title,

                'start' =>
                    $date->format('Y-m-d'),

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

        // HOTEL BOOKINGS
        $hotelBookings =

            Booking::where(
                'guest_id',
                $guest?->id
            )
            ->latest()
            ->get();

        // CONFERENCE BOOKINGS
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

        // PAYMENTS
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

        // return view(

        //     'profile',

        //     [

        //         'user' => $user,

        //         'guest' => $guest,

        //         'hotelBookings' =>
        //             $hotelBookings,

        //         'conferenceBookings' =>
        //             $conferenceBookings,

        //         'payments' =>
        //             $payments,

        //     ]
        // );
    }
)
->name('profile');

// Route::post(

//     '/profile/update',

//     function (
//         Illuminate\Http\Request
//         $request
//     ) {

//         $user = auth()->user();

//         if (
//             $request
//             ->hasFile(
//                 'profile_photo'
//             )
//         ) {

//             $path =
//                 $request
//                 ->file(
//                     'profile_photo'
//                 )
//                 ->store(
//                     'profile_photos',
//                     'public'
//                 );

//             $user->profile_photo =
//                 $path;
//         }

//         $user->phone_number =
//             $request->phone_number;

//         $guest->phone_number = 
//             $request->phone_number;

//         $user->save();
//         $guest->save();

//         return back()
//             ->with(
//                 'success',
//                 'Profile updated.'
//             );
//     }

// )->name(
//     'profile.update'
// );

// Route::post(

//     '/profile/update',

//     function (
//         Illuminate\Http\Request
//         $request
//     ) {

//         $request->validate([

//             'phone_number' =>

//                 'nullable|string|max:255',

//             'profile_photo' =>

//                 'nullable|image|max:2048',

//         ]);

//         $user =
//             auth()
//             ->user();

//         // PROFILE PHOTO
//         if (

//             $request
//                 ->hasFile(
//                     'profile_photo'
//                 )

//         ) {

//             $path =

//                 $request

//                 ->file(
//                     'profile_photo'
//                 )

//                 ->store(

//                     'profile_photos',

//                     'public'

//                 );

//             $user
//                 ->profile_photo =
//                     $path;
//         }

//         // UPDATE USER PHONE
//         $user->phone_number =

//             $request
//                 ->phone_number;

//         $user->save();


//         // UPDATE GUEST PHONE
//         $guest =

//             \App\Models\Guest::where(

//                 'user_id',

//                 $user->id

//             )->first();

//         if ($guest) {

//             $guest->phone_number =

//                 $request
//                     ->phone_number;

//             $guest->save();
//         }

//         return back()

//             ->with(

//                 'success',

//                 'Profile updated successfully.'

//             );
//     }

// )

// ->name(
//     'profile.update'
// );

// Route::post(

//     '/profile/password',

//     function (
//         Illuminate\Http\Request
//         $request
//     ) {

//         $request->validate([

//             'current_password' =>
//                 'required',

//             'password' =>
//                 'required|min:8'

//         ]);

//         $user =
//             auth()
//             ->user();

//         if (

//             !Hash::check(

//                 $request
//                     ->current_password,

//                 $user
//                     ->password
//             )
//         ) {

//             return back()

//                 ->withErrors([

//                     'current_password' =>

//                         'Wrong password.'

//                 ]);
//         }

//         $user->password =
//             bcrypt(
//                 $request
//                 ->password
//             );

//         $user->save();

//         return back()

//             ->with(
//                 'success',
//                 'Password updated.'
//             );
//     }

// )->name(
//     'profile.password'
// );

// Route::post(

//     '/profile/update',

//     function (
//         Illuminate\Http\Request
//         $request
//     ) {

//         $request->validate([

//             'phone_number' =>

//                 'nullable|string|max:255',

//             'profile_photo' =>

//                 'nullable|image|max:2048',

//         ]);

//         $user =
//             auth()
//             ->user();

//         $guest =

//             \App\Models\Guest::where(

//                 'user_id',

//                 $user->id

//             )->first();


//         // PROFILE PHOTO
//         if (

//             $request
//                 ->hasFile(
//                     'profile_photo'
//                 )

//         ) {

//             $path =

//                 $request

//                 ->file(
//                     'profile_photo'
//                 )

//                 ->store(

//                     'profile_photos',

//                     'public'

//                 );

//             // UPDATE USERS TABLE
//             $user
//                 ->profile_photo =
//                     $path;

//             // UPDATE GUESTS TABLE
//             if ($guest) {

//                 $guest
//                     ->profile_photo =
//                         $path;
//             }
//         }


//         // PHONE NUMBER
//         $phoneNumber =

//             $request
//                 ->phone_number;

//         // UPDATE USERS TABLE
//         $user->phone_number =
//             $phoneNumber;

//         // UPDATE GUESTS TABLE
//         if ($guest) {

//             $guest->phone_number =
//                 $phoneNumber;
//         }

//         // SAVE
//         $user->save();

//         if ($guest) {

//             $guest->save();
//         }

//         return back()

//             ->with(

//                 'success',

//                 'Profile updated successfully.'

//             );
//     }
// )
// ->name(
//     'profile.update'
// );

Route::post(

    '/profile/update',

    function (
        Illuminate\Http\Request
        $request
    ) {

        $request->validate([

            'phone_number' =>

                'nullable|string|max:255',

            'profile_photo' =>

                'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

        ]);

        $user =
            auth()
            ->user();

        $guest =
            \App\Models\Guest::where(
                'user_id',
                $user->id
            )->first();


        // HANDLE PROFILE PHOTO
        if (
            $request->hasFile(
                'profile_photo'
            )
        ) {

            $file =

                $request->file(
                    'profile_photo'
                );

            // STORE FILE
            $path =

                $file->store(

                    'profile_photos',

                    'public'

                );

            // SAVE TO USERS TABLE
            $user->profile_photo =
                $path;

            // SAVE TO GUEST TABLE
            if ($guest) {

                $guest->profile_photo =
                    $path;
            }
        }


        // PHONE NUMBER
        $phoneNumber =

            $request->phone_number;

        $user->phone_number =
            $phoneNumber;


        if ($guest) {

            $guest->phone_number =
                $phoneNumber;
        }


        // SAVE CHANGES
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
        Illuminate\Http\Request
        $request
    ) {

        $request->validate([

            'current_password' =>

                'required',

            'password' =>

                'required|min:8|confirmed',

        ]);

        $user =
            auth()
            ->user();

        // VERIFY OLD PASSWORD
        if (

            !Hash::check(

                $request
                    ->current_password,

                $user
                    ->password

            )

        ) {

            return back()

                ->withErrors([

                    'current_password' =>

                        'Current password is incorrect.'

                ]);
        }

        // UPDATE PASSWORD
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
    // Route::get('/profile', [ProfileController::class, 'edit'])
    //     ->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])
    //     ->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])
    //     ->name('profile.destroy');

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

            'hold_status' =>
                'expired',

            'payment_status' =>
                'expired',

        ]);

        $bookings =
            Booking::with([
                'room.roomType'
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

// Route::get('/rooms', [RoomController::class, 'index']);
// Route::get('/rooms', function () {
//     $rooms = Room::where('status', 'available')->get();

//     return view('rooms.index', compact('rooms'));
// });

Route::get('/rooms', function () {

    $roomTypes=RoomType::all();

    return view(
        'rooms.index',
        compact('roomTypes')
    );

})->name('rooms.index');

// Room type details
Route::get('/rooms/{type}', function (RoomType $type) {

    $availableRooms =
        Room::where('room_type_id',$type->id)
            // ->where('status','available')
            // ->whereDoesntHave('bookings',function($q){
            //     $q->whereDate('check_out','>=',today());
            // })
            ->whereDoesntHave(
                'bookings',
                // function ($query) {

                //     $query->whereDate(
                //         'check_in',
                //         '<=',
                //         today()
                //     )
                //     ->whereDate(
                //         'check_out',
                //         '>=',
                //         today()
                //     );

                // }

                function ($query) {

                    $query->where(function ($q) {

                        $q->where('hold_status', 'confirmed')

                            ->orWhere(function ($hold) {

                                $hold->where(
                                        'hold_status',
                                        'pending'
                                    )
                                    ->where(
                                        'hold_until',
                                        '>',
                                        now()
                                    );

                        });

                    });

                }
            )
            ->count();

    return view(
        'rooms.show',
        compact('type','availableRooms')
    );

})->name('rooms.show');

Route::get('/booking/expired', function () {

    return view('booking.expired');

});

Route::post('/booking/pay', function (Request $request) {

    $email = auth()->user()->email;
    $amount = session('booking.total') * 100; // pesewas

    $response = Http::withToken(config('services.paystack.secretKey'))
        ->post('https://api.paystack.co/transaction/initialize', [
            'email' => $email,
            'amount' => $amount,
            'callback_url' => route('payment.callback'),
            'metadata' => [
                'room_id' => session('booking.room_id'),
            ],
        ]);

    $data = $response->json();

    return redirect($data['data']['authorization_url']);

})->name('booking.pay');

Route::middleware('auth')->get(

    '/booking/{booking}/pay',

    function (Booking $booking) {

        // Prevent payment if already paid
        if ($booking->payment_status === 'paid') {
            return redirect()
                ->route('payments')
                ->with('error', 'This booking has already been paid.');
        }

        // Prevent payment if hold expired
        if ($booking->hold_status === 'expired') {
            return redirect()
                ->route('payments')
                ->with('error', 'This booking has expired.');
        }

        // Redirect to your existing booking payment page
        return redirect()->route('booking.payment', $booking);
    }

)->name('booking.pay.action');

Route::post(

    '/booking/{booking}/cancel',

    function (
        App\Models\Booking $booking
    ) {

        if (

            $booking
                ->payment_status
            === 'paid'

        ) {

            return back()

                ->with(
                    'error',
                    'Paid bookings cannot be cancelled.'
                );
        }

        $booking->update([

            'status' =>
                'cancelled',

            'payment_status' =>
                'cancelled',

            'hold_status' =>
                'cancelled',

        ]);

        return back()

            ->with(
                'success',
                'Booking cancelled.'
            );
    }

)->name(
    'booking.cancel'
);

Route::get(

    '/booking/{booking}/invoice',

    function (
        App\Models\Booking $booking
    ) {

        $pdf =
            Pdf::loadView(

                'invoice.hotel',

                compact(
                    'booking'
                )
            );

        return $pdf
            ->download(

                'hotel_invoice.pdf'
            );
    }

)->name(
    'booking.invoice'
);


Route::get('/payment/callback', function (Request $request) {

    $reference = $request->reference;

    $response = Http::withToken(config('services.paystack.secretKey'))
        ->get("https://api.paystack.co/transaction/verify/{$reference}");

    $data = $response->json();

    if ($data['data']['status'] === 'success') {

        // $booking = Booking::create([
        //     'user_id' => auth()->id(),
        //     'room_id' => session('booking.room_id'),
        //     'check_in' => session('booking.check_in'),
        //     'check_out' => session('booking.check_out'),
        //     'guests' => session('booking.guests'),
        //     'hold_status' => 'confirmed',
        //     'payment_reference' => $reference,
        // ]);

        $user = auth()->user();

        if (! $user) {

            return redirect('/login')
                ->with(
                    'message',
                    'Please login first.'
                );
        }

        // $guest = $user->guest;

        // Commented out 1/6/26 - 11:16
        // if (! $guest) {

        //     $guest =
        //         Guest::create([

        //             'user_id' =>
        //                 $user->id,

        //             'first_name' =>
        //                 $user->first_name,

        //             'last_name' =>
        //                 $user->last_name,

        //             'email' =>
        //                 $user->email,

        //             'phone_number' =>
        //                 $user->phone_number ?? '',

        //         ]);
        // }

        $guest =
            Guest::where(
                'user_id',
                $user->id
            )->first();

        // $booking = Booking::create([

        //         'guest_id' =>
        //             $guest->id,

        //         'room_id' =>
        //             session('booking.room_id'),

        //         'check_in' =>
        //             session('booking.check_in'),

        //         'check_out' =>
        //             session('booking.check_out'),

        //         'check_in_time' =>
        //             session('booking.check_in_time'),

        //         'check_out_time' =>
        //             session('booking.check_out_time'),

        //         'hold_status' =>
        //             'confirmed',

        //         'hold_until' =>
        //             now()->addMinutes(15),

        //         'total_price' =>
        //             session('booking.total'),
                    
        //         'payment_reference' =>
        //             $reference,

        //     ]);

        $booking =
            Booking::findOrFail(
                session('booking.id')
            );

        $booking->update([

            'hold_status' =>
                'confirmed',

            'payment_status' =>
                'paid',

            'transaction_reference' =>
                $reference,

            'hold_until' => null,

        ]);

        Payment::create([

            'booking_id' =>
                $booking->id,

            'amount' =>
                $booking->total_price,

            'method' =>
                'paystack',

            'payment_status' =>
                'paid',

            'transaction_reference' =>
                $reference,

        ]);

        session()->forget('booking');

        // return redirect('/booking/confirm/' . $booking->id);
        return redirect('/dashboard')
            ->with(
                'success',
                'Booking confirmed!'
            );

        // session()->forget('booking');

        // return redirect('/booking/confirm/'.$booking->id);
    }

    return redirect('/booking/payment')
        ->with('error', 'Payment failed');

})->name('payment.callback');

// Route::get(
//     '/rooms/{type}/available',
//     function (RoomType $type) {

//         $rooms = Room::where(
//             'room_type_id',
//             $type->id
//         )
//         ->whereDoesntHave(
//             'bookings',
//             function ($query) {

//                 $query->whereDate(
//                     'check_in',
//                     '<=',
//                     today()
//                 )
//                 ->whereDate(
//                     'check_out',
//                     '>=',
//                     today()
//                 );

//             }
//         )
//         ->orderBy('room_number')
//         ->get();

//         return view(
//             'rooms.available',
//             compact('type','rooms')
//         );

// })->name('rooms.available');

Route::middleware('auth')->get(
    '/rooms/{type}/available',
    function (RoomType $type) {

        $rooms = Room::where(
                'room_type_id',
                $type->id
            )
            ->whereDoesntHave(
                'bookings',
                function ($query) {

                    $query->whereDate(
                        'check_out',
                        '>=',
                        today()
                    );

                }
            )
            ->orderBy('room_number')
            ->get();

        return view(
            'rooms.available',
            compact('type', 'rooms')
        );

})->name('rooms.available');

Route::get('/book/{room}', function ($roomId) {
    $room = \App\Models\Room::findOrFail($roomId);

    return view('booking.create', compact('room'));
})->middleware('auth');

Route::post('/book', function () {

    $user = auth()->user();

    if (! $user) {

        return redirect('/login')
            ->with(
                'message',
                'Please login first.'
            );
    }
    
    // $guest = $user->guest;

    // Commented out 1/6/26 - 11:16
    // if (!$guest) {
    //     $guest = Guest::create([
    //         'user_id' => $user->id,
    //         'first_name' => $user->first_name,
    //         'last_name' => $user->last_name,
    //         'email' => $user->email,
    //         'phone' => $user->phone ?? null,
    //     ]);
    // }

    $guest =
        Guest::where(
            'user_id',
            $user->id
        )->first();

    $nights = \Carbon\Carbon::parse(request('check_in'))
        ->diffInDays(request('check_out'));

    $room = \App\Models\Room::find(request('room_id'));

    $total = $room->price * $nights;

    // $booking = Booking::create([
    //     'guest_id' => $guest->id,
    //     'room_id' => $room->id,
    //     'check_in' => request('check_in'),
    //     'check_out' => request('check_out'),
    //     'total_price' => $total,
    //     'status' => 'pending',
    // ]);

    $booking = Booking::create([

            'guest_id' =>
                $guest->id,

            'room_id' =>
                session('booking.room_id'),

            'check_in' =>
                session('booking.check_in'),

            'check_out' =>
                session('booking.check_out'),

            'check_in_time' =>
                session('booking.check_in_time'),

            'check_out_time' =>
                session('booking.check_out_time'),

            'hold_status' =>
                'pending',

            'payment_status' =>
                'unpaid',

            'hold_until' =>
                now()->addMinutes(15),

            'total_price' =>
                session('booking.total'),

        ]);

    return redirect('/pay/'.$booking->id);

});

Route::get('/pay/{booking}', function ($id) {

    $booking = \App\Models\Booking::findOrFail($id);

    return view('payments.pay', compact('booking'));
});

Route::get('/payment-success/{booking}', function ($id) {

    $booking = \App\Models\Booking::findOrFail($id);

    Payment::create([
        'booking_id' => $booking->id,
        'amount' => $booking->total_price,
        'method' => 'paystack',
        'transaction_reference' => request('ref'),
    ]);

    $booking->update([
        'status' => 'checked_in',
    ]);

    // Generate invoice
    InvoiceService::generate($booking);

    // Send email
    Mail::to(auth()->user()->email)
        ->send(new \App\Mail\InvoiceMail($booking));

    return redirect('/dashboard')->with('success', 'Payment successful!');

});

Route::middleware('auth')->group(function () {

    Route::get(
        '/booking/create',
        [App\Http\Controllers\BookingController::class,'create']
    )->name('booking.create');

    Route::post(
        '/booking/store',
        [App\Http\Controllers\BookingController::class,'store']
    )->name('booking.store');

Route::get(
    '/booking/select/{room}', 
    function (Room $room) {

        session([
            'booking.room_id' => $room->id,
            'booking.room_name' => $room->roomType->name,
            'booking.room_number' => $room->room_number,
            'booking.room_price' => $room->roomType->price_per_night ?? 0,
        ]);

        return redirect('/booking/details');

})->name('booking.select');

});


Route::middleware('auth')->group(function () {
    Route::get('/booking/details', function () {
        $roomId =
            session('booking.room_id');

        $bookings =
            \App\Models\Booking::where(
                'room_id',
                $roomId
            )->get();

        $disabledDates = [];

        foreach ($bookings as $booking) {

            $period =
                \Carbon\CarbonPeriod::create(
                    $booking->check_in,
                    $booking->check_out
                );

            foreach ($period as $date) {

                $disabledDates[] =
                    $date->format('Y-m-d');

            }
        }

        return view(
            'booking.details',
            compact('disabledDates')
        );
    })->name('booking.details');


    Route::post('/booking/details', function (Request $request) {

        // check if user is authenticated or redirect guest to login
        // if (! auth()->check()) {

        //     session([
        //         'url.intended' => url()->current()
        //     ]);

        //     return redirect('/login')
        //         ->with(
        //             'message',
        //             'Please login to continue booking.'
        //         );
        // }

        // check if user is authenticated or redirect guest to login
        if (! auth()->check()) {

            // preserve booking progress
            session([

                'url.intended' => url()->current(),

                'pending_booking' => [

                    'check_in' =>
                        $request->input('check_in'),

                    'check_out' =>
                        $request->input('check_out'),

                    'check_in_time' =>
                        $request->input('check_in_time'),

                    'check_out_time' =>
                        $request->input('check_out_time'),

                    'guests' =>
                        $request->input('guests'),

                    'room_id' =>
                        session('booking.room_id'),

                ]

            ]);

            return redirect('/login')
                ->with(
                    'message',
                    'Please login to continue booking.'
                );
        }

        $nights = \Carbon\Carbon::parse(
                $request->input('check_in')
            )->diffInDays(
                \Carbon\Carbon::parse(
                    $request->input('check_out')
                )
            );

        $nights = max($nights, 1);

        $total =
            session('booking.room_price') * $nights;

        $roomId =
            session('booking.room_id');

        $conflict =
            Booking::where(
                'room_id',
                $roomId
            )
            ->where(function ($query) use ($request) {

                $query->whereBetween(
                    'check_in',
                    [
                        $request->input('check_in'),
                        $request->input('check_out')
                    ]
                )
                ->orWhereBetween(
                    'check_out',
                    [
                        $request->input('check_in'),
                        $request->input('check_out')
                    ]
                );

            })
            ->exists();

        if ($conflict) {

            return back()
                ->withErrors([
                    'dates' =>
                        'Selected dates are unavailable.'
                ])
                ->withInput();
        }

        // $holdExpiresAt =
        //     now()->addMinutes(15);

        // $booking =
        //     Booking::create([

        //         'guest_id' =>
        //             auth()->user()->guest->id,

        //         'room_id' =>
        //             session('booking.room_id'),

        //         'check_in' =>
        //             $request->check_in,

        //         'check_out' =>
        //             $request->check_out,

        //         'check_in_time' =>
        //             $request->check_in_time,

        //         'check_out_time' =>
        //             $request->check_out_time,

        //         'total_price' =>
        //             session('booking.total'),

        //         'hold_expires_at' =>
        //             $holdExpiresAt,

        //         'status' =>
        //             'pending',

        //         'is_confirmed' =>
        //             false,

        //     ]);

        $holdUntil =
            now()->addMinutes(15);

        $user = auth()->user();

        if (! $user) {

            return redirect('/login')
                ->with(
                    'message',
                    'Please login to continue booking.'
                );
        }

        // Commented out 1/6/26 - 11:16
        // $guest = $user->guest;

        // if (! $guest) {

        //     $guest = Guest::create([

        //         'user_id' =>
        //             $user->id,

        //         'first_name' =>
        //             $user->first_name,

        //         'last_name' =>
        //             $user->last_name,

        //         'email' =>
        //             $user->email,

        //         'phone_number' =>
        //             $user->phone_number ?? '',

        //     ]);
        // }

        // Create a guest account
        $guest =
            Guest::firstOrCreate(

                [
                    'user_id' => $user->id
                ],

                [
                    'first_name' => $user->first_name,

                    'last_name' => $user->last_name,

                    'email' => $user->email,

                    'phone_number' => $user->phone_number ?? '',

                    'id_number' => $user->id_number ?? '',
                ]

            );

        $booking =
            Booking::create([

                'guest_id' =>
                    $guest->id,

                'room_id' =>
                    session('booking.room_id'),

                'check_in' =>
                    $request->input('check_in'),

                'check_out' =>
                    $request->input('check_out'),

                'check_in_time' =>
                    $request->input('check_in_time'),

                'check_out_time' =>
                    $request->input('check_out_time'),

                'hold_status' => 'pending',

                'hold_until' => $holdUntil,

                'total_price' =>
                    session('booking.total'),

            ]);

        session([
            'booking.check_in' => $request->check_in,
            'booking.check_out' => $request->check_out,
            // 'booking.guests' => $request->guests,
            'booking.guests' => $request->guest_id,
            'booking.nights' => $nights,
            'booking.total' => $total,
            'booking.id' => $booking->id,
        ]);

        

        // session([
        //     'booking.id' =>
        //         $booking->id
        // ]);

        return redirect('/booking/payment');

    });


    // Route::get('/booking/payment', function () {
    //     return view('booking.payment');
    // })->name('booking.payment');

    Route::get('/booking/payment', function () {

        $bookingId =
            session('booking.id');

        if (! $bookingId) {

            return redirect('/rooms')
                ->with(
                    'error',
                    'Booking session expired.'
                );
        }

        $booking =
            Booking::find(
                $bookingId
            );

        if (! $booking) {

            return redirect('/rooms')
                ->with(
                    'error',
                    'Booking not found.'
                );
        }

        // dd([
        //     'booking_id' =>
        //         session('booking.id'),

        //     'hold_until' =>
        //         $booking?->hold_until,

        //     'now' =>
        //         now(),
        // ]);

        return view(
            'booking.payment',
            compact('booking')
        );

    })->name('booking.payment');


    Route::post('/booking/payment', function () {

        $user = auth()->user();

        if (! $user) {

            return redirect('/login');
        }

        // $guest = $user->guest;

        // Commented out 1/6/26 - 11:16
        // if (! $guest) {

        //     $guest =
        //         Guest::create([

        //             'user_id' =>
        //                 $user->id,

        //             'first_name' =>
        //                 $user->first_name,

        //             'last_name' =>
        //                 $user->last_name,

        //             'email' =>
        //                 $user->email,

        //             'phone_number' =>
        //                 $user->phone_number ?? '',

        //         ]);
        // }

        $guest =
            Guest::where(
                'user_id',
                $user->id
            )->first();

        // $booking = Booking::create([
        //     'user_id' => auth()->id(),
        //     'room_id' => session('booking.room_id'),
        //     'check_in' => session('booking.check_in'),
        //     'check_out' => session('booking.check_out'),
        //     'guests' => session('booking.guests'),
        //     'hold_status' => 'confirmed',
        // ]);

        // $booking = Booking::create([

        //         'guest_id' =>
        //             $guest->id,

        //         'room_id' =>
        //             session('booking.room_id'),

        //         'check_in' =>
        //             session('booking.check_in'),

        //         'check_out' =>
        //             session('booking.check_out'),

        //         'check_in_time' =>
        //             session('booking.check_in_time'),

        //         'check_out_time' =>
        //             session('booking.check_out_time'),

        //         'hold_status' =>
        //             'confirmed',

        //         'total_price' =>
        //             session('booking.total'),

        //     ]);

        // session()->forget('booking');

        // return redirect('/booking/confirm/'.$booking->id);

        return redirect()->route('booking.pay');

    });


    Route::get('/booking/confirm/{booking}', function (Booking $booking) {
        return view('booking.confirm', compact('booking'));
    })->name('booking.confirm');
});





require __DIR__.'/auth.php';
