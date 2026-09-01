<?php

namespace App\Services;

use App\Models\Room;

/**
 * Encapsulates business rules for room assignment service.
 */
class RoomAssignmentService
{
    /**
     * Selects an available physical room for the requested room type and stay dates.
     */
    public static function assignRoom($roomTypeId, $checkIn, $checkOut): ?Room
    {
        return app(RoomAvailabilityService::class)
            ->query($checkIn, $checkOut)
            ->where('room_type_id', $roomTypeId)
            ->orderBy('room_number')
            ->first();
    }
}
