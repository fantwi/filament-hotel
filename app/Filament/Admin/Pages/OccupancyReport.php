<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantReservation;
use App\Models\Room;
use App\Services\CurrentVenueAvailability;
use Carbon\Carbon;
use Filament\Pages\Page;

/**
 * Provides the occupancy report Filament administration page.
 */
class OccupancyReport extends Page
{
    use InteractsWithReportPeriod;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Occupancy Report';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.admin.pages.occupancy-report';

    /**
     * Configures report for the Filament administration interface.
     */
    public function report(): array
    {
        [$periodStart, $periodEnd] = $this->periodBounds();
        $periodEndExclusive = $periodEnd->copy()->addDay()->startOfDay();

        $roomStatus = [
            'total' => Room::count(),
            'occupied' => Room::where('status', 'occupied')->count(),
            'available' => Room::where('status', 'available')->count(),
            'maintenance' => Room::where('status', 'maintenance')->count(),
        ];

        $roomsInService = $roomStatus['total'] - $roomStatus['maintenance'];

        $hotelBookings = Booking::query()
            ->whereDate('check_in', '<', $periodEndExclusive->toDateString())
            ->whereDate('check_out', '>', $periodStart->toDateString())
            ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
            ->count();

        $bookedRoomNights = $this->bookedRoomNights($periodStart, $periodEndExclusive);

        $periodDays = max(1, (int) $periodStart->diffInDays($periodEndExclusive));
        $roomNightCapacity = $roomsInService * $periodDays;

        $conferenceBookings = ConferenceBooking::query()
            ->whereBetween('booking_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        $tableReservations = RestaurantReservation::query()
            ->whereBetween('reservation_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        $conferenceAvailability = app(CurrentVenueAvailability::class)->conferenceRooms(now());

        $tableStatus = app(CurrentVenueAvailability::class)->restaurantTables(now());

        return [
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'roomStatus' => $roomStatus,
            'roomsInService' => $roomsInService,
            'hotelBookings' => $hotelBookings,
            'bookedRoomNights' => $bookedRoomNights,
            'roomNightCapacity' => $roomNightCapacity,
            'occupancyRate' => $roomNightCapacity > 0 ? ($bookedRoomNights / $roomNightCapacity) * 100 : 0,
            'conferenceBookings' => $conferenceBookings,
            'tableReservations' => $tableReservations,
            'conferenceAvailability' => $conferenceAvailability,
            'tableStatus' => $tableStatus,
        ];
    }

    /**
     * Streams overlapping stays and clamps each stay to the selected period.
     *
     * This keeps memory usage stable even when an all-time report covers a
     * large booking history.
     */
    private function bookedRoomNights(Carbon $periodStart, Carbon $periodEnd): int
    {
        return Booking::query()
            ->whereDate('check_in', '<', $periodEnd->toDateString())
            ->whereDate('check_out', '>', $periodStart->toDateString())
            ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
            ->select(['id', 'check_in', 'check_out'])
            ->lazyById()
            ->sum(function (Booking $booking) use ($periodStart, $periodEnd): int {
                $checkIn = Carbon::parse($booking->check_in)->max($periodStart);
                $checkOut = Carbon::parse($booking->check_out)->min($periodEnd);

                return max(0, $checkIn->diffInDays($checkOut));
            });
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'receptionist']) ?? false;
    }
}
