<?php

use Illuminate\Support\Facades\Route;
use App\Models\Booking;
use App\Models\Room;
use App\Http\Controllers\BookingController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->get('/admin/calendar-events', function () {
    
    return Booking::with(['guest', 'room'])->get()->map(function ($booking) {

        return [
            'title' => $booking->guest->full_name,
            'start' => $booking->check_in,
            'end'   => $booking->check_out,
            'editable' => $booking->status === 'pending',
            'color' => match ($booking->status) {
                'pending' => '#f59e0b',
                'checked_in' => '#3b82f6',
                'checked_out' => '#10b981',
                default => '#6b7280',
            },
            'extendedProps' => [
                // 👇 Extended Props
                'booking_id' => $booking->id,
                'room' => $booking->room->room_number,
                'status' => $booking->status,
                'total_price' => $booking->total_price,
                'balance' => $booking->balance,
                'check_in' => $booking->check_in,
                'check_out' => $booking->check_out,
            ],
            // 'description' => 'Room: ' . $booking->room->room_number .
            //     ' | Status: ' . ucfirst($booking->status) .
            //     ' | Balance: GHS ' . number_format($booking->balance, 2),

            
        ];
    });
});

Route::middleware(['auth'])->post(
    '/admin/bookings/{booking}/reschedule',
    [BookingController::class, 'reschedule']
);

Route::get('/admin/timeline-rooms', function () {

    return Room::all()->map(fn($room) => [

        'id' => $room->id,
        'title' => 'Room '.$room->room_number

    ]);

});

Route::get('/admin/timeline-bookings', function () {

    return Booking::with('guest')->get()->map(function ($booking) {

        return [

            'id' => $booking->id,

            'resourceId' => $booking->room_id,

            'title' => $booking->guest->full_name,

            'start' => $booking->check_in,

            'end' => $booking->check_out,

            'color' => match($booking->status) {

            'pending' => '#f59e0b',
            'checked_in' => '#10b981',
            'checked_out' => '#6b7280',

            }

        ];

    });

});

Route::post(
    '/admin/bookings/{booking}/timeline-update',
    [BookingController::class,'timelineUpdate']
)->middleware('auth');

Route::get('/invoice/{invoice}', function ($invoice) {

    $booking = Booking::where('invoice_number', $invoice)->firstOrFail();

    return view('invoice.verify', compact('booking'));

});

// Route::middleware(['auth'])->group(function () {

//     Route::get('/admin/calendar-events', function () {
//         ...
//     });

// });