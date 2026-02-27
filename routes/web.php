<?php

use Illuminate\Support\Facades\Route;
use App\Models\Booking;

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
    [\App\Http\Controllers\BookingController::class, 'reschedule']
);

// Route::middleware(['auth'])->group(function () {

//     Route::get('/admin/calendar-events', function () {
//         ...
//     });

// });