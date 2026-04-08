<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function calendarEvents(): JsonResponse
    {
        $this->authorizeBookingAccess();

        $bookings = Booking::query()
            ->with(['guest', 'room'])
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id' => (string) $booking->id,
                    'booking_id' => $booking->id,
                    'title' => $booking->guest?->full_name ?? 'Unknown Guest',
                    'start' => $booking->check_in?->toDateString(),
                    'end' => $booking->check_out?->copy()->addDay()->toDateString(),
                    'room' => $booking->room?->room_number,
                    'check_in' => $booking->check_in?->toDateString(),
                    'check_out' => $booking->check_out?->toDateString(),
                    'total_price' => $booking->total_price,
                    'balance' => $booking->balance,
                    'status' => $booking->status,
                ];
            })
            ->values();

        return response()->json($bookings);
    }

    public function timelineUpdate(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBookingAccess();

        $data = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending bookings can move.',
            ], 422);
        }

        $conflict = Booking::where('room_id', $data['room_id'])
            ->whereKeyNot($booking->id)
            ->overlapping($data['check_in'], $data['check_out'])
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Room is already booked for those dates.',
            ], 422);
        }

        $booking->update($data);

        return response()->json([
            'success' => true,
        ]);
    }

    public function reschedule(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBookingAccess();

        $data = $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending bookings can be rescheduled.',
            ], 422);
        }

        $newCheckIn = Carbon::parse($data['check_in']);
        $newCheckOut = Carbon::parse($data['check_out']);

        $conflict = Booking::where('room_id', $booking->room_id)
            ->where('id', '!=', $booking->id)
            ->overlapping($newCheckIn->toDateString(), $newCheckOut->toDateString())
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Room is already booked for those dates.',
            ], 422);
        }

        $days = $newCheckIn->diffInDays($newCheckOut);
        $price = $booking->room->roomType->price_per_night;

        $booking->update([
            'check_in' => $newCheckIn,
            'check_out' => $newCheckOut,
            'total_price' => max($days, 1) * $price,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    private function authorizeBookingAccess(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['admin', 'receptionist']),
            403
        );
    }
}
