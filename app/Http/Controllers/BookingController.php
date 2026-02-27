<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    //
    public function reschedule(Request $request, Booking $booking)
    {
        // Only allow pending bookings to move
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending bookings can be rescheduled.'
            ]);
        }

        $newCheckIn = Carbon::parse($request->check_in);
        $newCheckOut = Carbon::parse($request->check_out);

        // Prevent overlapping
        $conflict = Booking::where('room_id', $booking->room_id)
            ->where('id', '!=', $booking->id)
            ->where(function ($query) use ($newCheckIn, $newCheckOut) {
                $query->whereBetween('check_in', [$newCheckIn, $newCheckOut])
                    ->orWhereBetween('check_out', [$newCheckIn, $newCheckOut])
                    ->orWhere(function ($q) use ($newCheckIn, $newCheckOut) {
                        $q->where('check_in', '<=', $newCheckIn)
                          ->where('check_out', '>=', $newCheckOut);
                    });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Room is already booked for those dates.'
            ]);
        }

        // Recalculate price
        $days = $newCheckIn->diffInDays($newCheckOut);
        $price = $booking->room->roomType->price_per_night;

        $booking->update([
            'check_in' => $newCheckIn,
            'check_out' => $newCheckOut,
            'total_price' => max($days, 1) * $price,
        ]);

        return response()->json([
            'success' => true
        ]);
    }
}
