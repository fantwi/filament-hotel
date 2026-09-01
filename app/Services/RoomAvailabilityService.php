<?php

namespace App\Services;

use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resolves rooms that can be booked for a given stay period.
 */
class RoomAvailabilityService
{
    /**
     * Builds the query for rooms that have no active booking overlapping the stay period.
     *
     * @return Builder<Room>
     */
    public function query(string|CarbonInterface $checkIn, string|CarbonInterface $checkOut, ?int $exceptBookingId = null): Builder
    {
        return Room::query()
            ->with('roomType')
            ->where('status', '!=', 'maintenance')
            ->whereDoesntHave('bookings', function (Builder $query) use ($checkIn, $checkOut, $exceptBookingId): void {
                $query
                    ->when($exceptBookingId, fn (Builder $query) => $query->whereKeyNot($exceptBookingId))
                    ->whereNotIn('status', ['cancelled', 'no_show'])
                    ->where(fn (Builder $query) => $query
                        ->whereNull('hold_status')
                        ->orWhere('hold_status', '!=', 'expired'))
                    ->overlapping($checkIn, $checkOut);
            });
    }

    /**
     * Determines whether a particular room is available for a given stay period.
     */
    public function isAvailable(int $roomId, string|CarbonInterface $checkIn, string|CarbonInterface $checkOut, ?int $exceptBookingId = null): bool
    {
        return $this->query($checkIn, $checkOut, $exceptBookingId)
            ->whereKey($roomId)
            ->exists();
    }
}
