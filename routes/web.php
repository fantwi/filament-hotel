<?php

use Illuminate\Support\Facades\Route;
use App\Models\Booking;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/calendar-events', function () {

        return Booking::with(['guest', 'room'])->get()->map(function ($booking) {

            return [
                'title' => $booking->guest->full_name . ' - Room ' . $booking->room->room_number,
                'start' => $booking->check_in,
                'end'   => $booking->check_out,
                'color' => match ($booking->status) {
                    'pending' => '#f59e0b',
                    'checked_in' => '#3b82f6',
                    'checked_out' => '#10b981',
                    default => '#6b7280',
                },
            ];
        });

    });
});

// Route::middleware(['auth'])->group(function () {

//     Route::get('/admin/calendar-events', function () {
//         ...
//     });

// });