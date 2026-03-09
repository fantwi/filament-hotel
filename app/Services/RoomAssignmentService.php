<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Booking;

class RoomAssignmentService
{
    public static function assignRoom($roomTypeId, $checkIn, $checkOut)
    {
        $rooms = Room::where('room_type_id', $roomTypeId)
            ->where('status', 'available')
            ->orderBy('room_number')
            ->get();

        foreach ($rooms as $room) {

            $conflict = Booking::where('room_id', $room->id)
                ->where(function ($query) use ($checkIn, $checkOut) {
                    $query->whereBetween('check_in', [$checkIn, $checkOut])
                        ->orWhereBetween('check_out', [$checkIn, $checkOut])
                        ->orWhere(function ($q) use ($checkIn, $checkOut) {
                            $q->where('check_in', '<=', $checkIn)
                              ->where('check_out', '>=', $checkOut);
                        });
                })
                ->exists();

            if (!$conflict) {
                return $room;
            }
        }

        return null;
    }
}