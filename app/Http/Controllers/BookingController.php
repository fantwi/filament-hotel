<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantReservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function create(Request $request)
    {
        $room = Room::findOrFail(
            $request->room_id
        );

        return view(
            'bookings.create',
            compact('room')
        );
    }


    public function store(Request $request)
    {
        $request->validate([
            'room_id'=>'required',
            'check_in'=>'required|date',
            'check_out'=>'required|date',
        ]);

        $booked = Booking::where('room_id', $request->room_id)
            ->where(function($query) use($request) {
                $query->whereBetween('check_in', [
                    $request->check_in,
                    $request->check_out
                ]);
            })
            ->exists();

        if($booked){
            return back()
            ->withErrors(
                ['Room no longer available']
            );
        }

        Booking::create([
            'guest_id' =>
                auth()->user()->guest->id,

            'room_id' =>
                $request->room_id,

            'check_in' =>
                $request->check_in,

            'check_out' =>
                $request->check_out,

            'hold_status' => 'confirmed',
        ]);


        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'Booking created successfully'
            );
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $this->authorizeBookingAccess();

        $start = $request->date('start');
        $end = $request->date('end');

        $colorForStatus = fn (?string $status): string => match ($status) {
            'confirmed' => '#22c55e',
            'pending' => '#f59e0b',
            'checked_in' => '#3b82f6',
            'cancelled' => '#ef4444',
            default => '#9ca3af',
        };

        $hotelBookings = Booking::query()
            ->with(['guest', 'room'])
            ->when($start && $end, fn ($query) => $query
                ->whereDate('check_in', '<', $end)
                ->whereDate('check_out', '>', $start))
            ->get()
            ->map(function (Booking $booking) use ($colorForStatus) {
                return [
                    'id' => "hotel-{$booking->id}",
                    'title' => 'Hotel - Room '.($booking->room?->room_number ?? 'Unknown'),
                    'start' => $booking->check_in?->toDateString(),
                    'end' => $booking->check_out?->toDateString(),
                    'allDay' => true,
                    'color' => $colorForStatus($booking->status),
                    'url' => route('filament.admin.resources.bookings.view', $booking),
                    'extendedProps' => [
                        'type' => 'Hotel booking',
                        'guest' => $booking->guest?->full_name ?? 'Unknown Guest',
                        'status' => $booking->status,
                        'details' => "Room {$booking->room?->room_number}; {$booking->check_in?->toDateString()} to {$booking->check_out?->toDateString()}",
                    ],
                ];
            });

        $conferenceBookings = ConferenceBooking::query()
            ->with(['guest', 'room'])
            ->when($start && $end, fn ($query) => $query
                ->whereDate('booking_date', '>=', $start)
                ->whereDate('booking_date', '<', $end))
            ->get()
            ->map(function (ConferenceBooking $booking) use ($colorForStatus) {
                $date = $booking->booking_date?->toDateString();

                return [
                    'id' => "conference-{$booking->id}",
                    'title' => 'Conference - '.($booking->room?->name ?? 'Unknown Room'),
                    'start' => "{$date} {$booking->start_time}",
                    'end' => "{$date} {$booking->end_time}",
                    'color' => $colorForStatus($booking->status),
                    'extendedProps' => [
                        'type' => 'Conference booking',
                        'guest' => $booking->guest?->full_name ?? 'Unknown Guest',
                        'status' => $booking->status,
                        'details' => "{$booking->room?->name}; {$date} {$booking->start_time}–{$booking->end_time}",
                    ],
                ];
            });

        $restaurantReservations = RestaurantReservation::query()
            ->with(['restaurant', 'table'])
            ->when($start && $end, fn ($query) => $query
                ->whereDate('reservation_date', '>=', $start)
                ->whereDate('reservation_date', '<', $end))
            ->get()
            ->map(function (RestaurantReservation $reservation) use ($colorForStatus) {
                $date = $reservation->reservation_date?->toDateString();
                $startAt = Carbon::parse("{$date} {$reservation->reservation_time}");

                return [
                    'id' => "restaurant-{$reservation->id}",
                    'title' => 'Restaurant - Table '.($reservation->table?->table_number ?? 'Unknown'),
                    'start' => $startAt->toDateTimeString(),
                    'end' => $startAt->copy()->addMinutes($reservation->duration_minutes ?? 120)->toDateTimeString(),
                    'color' => $colorForStatus($reservation->status),
                    'url' => route('filament.admin.resources.restaurant-reservations.edit', $reservation),
                    'extendedProps' => [
                        'type' => 'Restaurant reservation',
                        'guest' => $reservation->guest_name,
                        'status' => $reservation->status,
                        'details' => "{$reservation->restaurant?->name}; Table {$reservation->table?->table_number}",
                    ],
                ];
            });

        return response()->json(
            $hotelBookings
                ->concat($conferenceBookings)
                ->concat($restaurantReservations)
                ->values()
        );
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
