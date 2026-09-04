<?php

namespace App\Services;

use App\Models\ConferenceRoom;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Calculates venue availability for one point in time.
 */
class CurrentVenueAvailability
{
    /**
     * @return array{available: int, unavailable: int}
     */
    public function conferenceRooms(CarbonInterface $at): array
    {
        $total = ConferenceRoom::query()->count();
        $time = $at->format('H:i:s');

        $available = ConferenceRoom::query()
            ->where('is_available', true)
            ->whereDoesntHave('bookings', function ($query) use ($at, $time): void {
                $query
                    ->whereDate('booking_date', $at->toDateString())
                    ->where('start_time', '<=', $time)
                    ->where('end_time', '>', $time)
                    ->where(function ($statusQuery) use ($at): void {
                        $statusQuery
                            ->whereIn('status', ['confirmed', 'checked_in'])
                            ->orWhere(function ($pendingQuery) use ($at): void {
                                $pendingQuery
                                    ->where('status', 'pending')
                                    ->where('hold_until', '>', $at);
                            });
                    });
            })
            ->count();

        return [
            'available' => $available,
            'unavailable' => $total - $available,
        ];
    }

    /**
     * Returns mutually exclusive live table-status totals.
     *
     * A stored "reserved" status is not authoritative because it may have
     * been set by a future reservation. The reservation schedule determines
     * whether a table is reserved at the requested instant.
     *
     * @return array{available: int, reserved: int, occupied: int, unavailable: int}
     */
    public function restaurantTables(CarbonInterface $at): array
    {
        $unavailableTableIds = RestaurantTable::query()
            ->whereIn('status', ['cleaning', 'maintenance'])
            ->pluck('id');
        $occupiedTableIds = RestaurantTable::query()
            ->where('status', 'occupied')
            ->pluck('id');

        $reservedTableIds = RestaurantReservation::query()
            ->whereDate('reservation_date', $at->toDateString())
            ->where(function ($query) use ($at): void {
                $query
                    ->whereIn('status', ['confirmed', 'checked_in'])
                    ->orWhere(function ($pendingQuery) use ($at): void {
                        $pendingQuery
                            ->where('status', 'pending')
                            ->where('hold_until', '>', $at);
                    });
            })
            ->get(['id', 'restaurant_table_id', 'reservation_date', 'reservation_time', 'duration_minutes'])
            ->filter(function (RestaurantReservation $reservation) use ($at): bool {
                $startsAt = Carbon::parse(
                    $at->toDateString().' '.$reservation->getRawOriginal('reservation_time'),
                    $at->getTimezone(),
                );
                $endsAt = $startsAt->copy()->addMinutes((int) ($reservation->duration_minutes ?: 120));

                return $at->greaterThanOrEqualTo($startsAt) && $at->lessThan($endsAt);
            })
            ->pluck('restaurant_table_id')
            ->unique()
            ->diff($unavailableTableIds)
            ->diff($occupiedTableIds);

        $total = RestaurantTable::query()->count();
        $unavailable = $unavailableTableIds->count();
        $occupied = $occupiedTableIds->diff($unavailableTableIds)->count();
        $reserved = $reservedTableIds->count();

        return [
            'available' => max(0, $total - $unavailable - $occupied - $reserved),
            'reserved' => $reserved,
            'occupied' => $occupied,
            'unavailable' => $unavailable,
        ];
    }
}
