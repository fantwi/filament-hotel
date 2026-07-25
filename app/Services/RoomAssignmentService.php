<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;

class RoomAssignmentService
{
    public static function assignRoom($roomTypeId, $checkIn, $checkOut)
    {
        $rooms = Room::where('room_type_id', $roomTypeId)
            ->where('status', '!=', 'maintenance')
            ->orderBy('room_number')
            ->get();

        foreach ($rooms as $room) {
            $conflict = Booking::where('room_id', $room->id)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->where(function ($query) {
                    $query->whereNull('hold_status')
                        ->orWhere('hold_status', '!=', 'expired');
                })
                ->overlapping($checkIn, $checkOut)
                ->exists();

            if (! $conflict) {
                return $room;
            }
        }

        return null;
    }
}
