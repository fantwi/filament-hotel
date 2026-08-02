<?php

use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/rooms', function () {

    $roomTypes = RoomType::published()->get();

    return view(
        'rooms.index',
        compact('roomTypes')
    );

})->name('rooms.index');

Route::get('/rooms/{type}', function (RoomType $type) {

    abort_unless($type->is_published, 404);

    $type->load(['facilities' => fn ($query) => $query->published()]);

    $availableRooms = Room::query()
        ->where('room_type_id', $type->id)
        ->where('status', '!=', 'maintenance')
        ->count();

    return view(
        'rooms.show',
        compact('type', 'availableRooms')
    );

})->name('rooms.show');

Route::get('/booking/expired', function () {

    return view('booking.expired');

});

Route::post('/booking/pay', function (Request $request) {

    $booking = Booking::query()->findOrFail(session('booking.id'));

    abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

    if ($booking->hold_until?->isPast() || $booking->payments()->where('payment_status', 'paid')->exists()) {
        return redirect()->route('booking.payment')->with('error', 'This booking is no longer available for payment.');
    }

    $organization = app(\App\Services\CorporateCreditService::class)->organizationFor(auth()->user());

    if ($organization) {
        $booking->update([
            'corporate_organization_id' => $organization->id,
            'status' => 'confirmed',
            'hold_status' => 'confirmed',
            'hold_until' => null,
        ]);

        return redirect()->route('dashboard')
            ->with('success', "Room booking confirmed and billed to {$organization->name}.");

    }
    $reference = 'ROOM-'.Str::upper(Str::random(16));
    $booking->update(['transaction_reference' => $reference]);

    $response = Http::withToken(config('services.paystack.secretKey'))
        ->post('https://api.paystack.co/transaction/initialize', [
            'email' => auth()->user()->email,
            'amount' => (int) round($booking->total_price * 100),
            'reference' => $reference,
            'callback_url' => route('payment.callback'),
            'metadata' => [
                'booking_id' => $booking->id,
            ],
        ]);

    $data = $response->json();

    if (! $response->successful() || ! isset($data['data']['authorization_url'])) {
        return back()->with('error', 'Unable to initialize payment. Please try again.');
    }

    return redirect()->away($data['data']['authorization_url']);

})->middleware('auth')->name('booking.pay');

Route::middleware('auth')->get(

    '/booking/{booking}/pay',

    function (Booking $booking) {

        abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

        if ($booking->payment_status === 'paid') {
            return redirect()
                ->route('payments')
                ->with('error', 'This booking has already been paid.');
        }

        if ($booking->hold_status === 'expired') {
            return redirect()
                ->route('payments')
                ->with('error', 'This booking has expired.');
        }

        return redirect()->route('booking.payment', $booking);
    }

)->name('booking.pay.action');

Route::post(

    '/booking/{booking}/cancel',

    function (
        Booking $booking
    ) {

        abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

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

            'status' => 'cancelled',

            'payment_status' => 'cancelled',

            'hold_status' => 'cancelled',

        ]);

        session()->forget(['booking.id', 'booking.total']);

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Booking cancelled. The room hold has been released.');
    }

)->middleware('auth')->name(
    'booking.cancel'
);

Route::get(

    '/booking/{booking}/invoice',

    function (
        Booking $booking
    ) {

        abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

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

)->middleware('auth')->name(
    'booking.invoice'
);

Route::get('/payment/callback', function (Request $request) {

    $reference = $request->reference;

    if (! $reference) {
        return redirect('/rooms')->with('error', 'Missing payment reference.');
    }

    $booking = Booking::query()->where('transaction_reference', $reference)->firstOrFail();

    $response = Http::withToken(config('services.paystack.secretKey'))
        ->get("https://api.paystack.co/transaction/verify/{$reference}");

    $data = $response->json();

    if (($data['data']['status'] ?? null) === 'success'
        && ($data['data']['reference'] ?? null) === $reference
        && (int) ($data['data']['amount'] ?? 0) === (int) round($booking->total_price * 100)
        && (int) data_get($data, 'data.metadata.booking_id') === $booking->id) {

        DB::transaction(function () use ($booking, $reference): void {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->payments()->where('payment_status', 'paid')->exists()) {
                return;
            }

            $booking->update(['hold_status' => 'confirmed', 'payment_status' => 'paid', 'hold_until' => null]);
            Payment::firstOrCreate(['transaction_reference' => $reference], [
                'booking_id' => $booking->id,
                'guest_id' => $booking->guest_id,
                'amount' => $booking->total_price,
                'method' => 'paystack',
                'payment_status' => 'paid',
            ]);
        });

        session()->forget('booking');

        return redirect('/dashboard')
            ->with(
                'success',
                'Booking confirmed!'
            );

    }

    return redirect('/booking/payment')
        ->with('error', 'Payment failed');

})->name('payment.callback');

Route::middleware('auth')->get(
    '/rooms/{type}/available',
    function (RoomType $type) {

        abort_unless($type->is_published, 404);

        $rooms = Room::query()
            ->where('room_type_id', $type->id)
            ->where('status', '!=', 'maintenance')
            ->orderBy('room_number')
            ->get();

        return view(
            'rooms.available',
            compact('type', 'rooms')
        );

    })->name('rooms.available');

Route::get('/book/{room}', function ($roomId) {
    $room = Room::findOrFail($roomId);

    return view('booking.create', compact('room'));
})->middleware('auth');

Route::post('/book', function () {

    abort(410, 'This legacy booking endpoint has been retired.');

    $user = auth()->user();

    if (! $user) {

        return redirect('/login')
            ->with(
                'message',
                'Please login first.'
            );
    }

    $guest =
        Guest::where(
            'user_id',
            $user->id
        )->first();

    $nights = Carbon::parse(request('check_in'))
        ->diffInDays(request('check_out'));

    $room = Room::find(request('room_id'));

    $total = $room->price * $nights;

    $booking = Booking::create([

        'guest_id' => $guest->id,

        'room_id' => session('booking.room_id'),

        'check_in' => session('booking.check_in'),

        'check_out' => session('booking.check_out'),

        'check_in_time' => session('booking.check_in_time'),

        'check_out_time' => session('booking.check_out_time'),

        'hold_status' => 'pending',

        'payment_status' => 'unpaid',

        'hold_until' => now()->addMinutes(15),

        'total_price' => session('booking.total'),

    ]);

    session([
        'booking.id' => $booking->id,
        'booking.total' => $booking->total_price,
    ]);

    return redirect()->route('booking.payment');

});

Route::get('/pay/{booking}', function ($id) {

    $booking = Booking::findOrFail($id);

    abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

    session([
        'booking.id' => $booking->id,
        'booking.total' => $booking->total_price,
    ]);

    return redirect()->route('booking.payment');
})->middleware('auth');

Route::get('/payment-success/{booking}', function ($id) {
    abort(410, 'This legacy payment callback has been retired.');
});

Route::middleware('auth')->group(function () {

    Route::get(
        '/booking/create',
        [BookingController::class, 'create']
    )->name('booking.create');

    Route::post(
        '/booking/store',
        [BookingController::class, 'store']
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
            Booking::where(
                'room_id',
                $roomId
            )->get();

        $disabledDates = [];

        foreach ($bookings as $booking) {

            $period =
                CarbonPeriod::create(
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

        if (! auth()->check()) {

            session([

                'url.intended' => url()->current(),

                'pending_booking' => [

                    'check_in' => $request->input('check_in'),

                    'check_out' => $request->input('check_out'),

                    'check_in_time' => $request->input('check_in_time'),

                    'check_out_time' => $request->input('check_out_time'),

                    'guests' => $request->input('guests'),

                    'room_id' => session('booking.room_id'),

                ],

            ]);

            return redirect('/login')
                ->with(
                    'message',
                    'Please login to continue booking.'
                );
        }

        $nights = Carbon::parse(
            $request->input('check_in')
        )->diffInDays(
            Carbon::parse(
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
                            $request->input('check_out'),
                        ]
                    )
                        ->orWhereBetween(
                            'check_out',
                            [
                                $request->input('check_in'),
                                $request->input('check_out'),
                            ]
                        );

                })
                ->exists();

        if ($conflict) {

            return back()
                ->withErrors([
                    'dates' => 'Selected dates are unavailable.',
                ])
                ->withInput();
        }

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

        $guest =
            Guest::firstOrCreate(

                [
                    'user_id' => $user->id,
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

                'guest_id' => $guest->id,

                'room_id' => session('booking.room_id'),

                'check_in' => $request->input('check_in'),

                'check_out' => $request->input('check_out'),

                'check_in_time' => $request->input('check_in_time'),

                'check_out_time' => $request->input('check_out_time'),

                'hold_status' => 'pending',

                'hold_until' => $holdUntil,

                'total_price' => session('booking.total'),

            ]);

        session([
            'booking.check_in' => $request->check_in,
            'booking.check_out' => $request->check_out,
            'booking.guests' => $request->guest_id,
            'booking.nights' => $nights,
            'booking.total' => $total,
            'booking.id' => $booking->id,
        ]);

        return redirect('/booking/payment');

    });

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

        $guest =
            Guest::where(
                'user_id',
                $user->id
            )->first();

        return redirect()->route('booking.pay');

    });

    Route::get('/booking/confirm/{booking}', function (Booking $booking) {
        abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

        return view('booking.confirm', compact('booking'));
    })->name('booking.confirm');
});
