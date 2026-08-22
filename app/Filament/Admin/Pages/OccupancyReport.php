<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use Carbon\Carbon;
use Filament\Pages\Page;

/**
 * Provides the occupancy report Filament administration page.
 */
class OccupancyReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Occupancy Report';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.admin.pages.occupancy-report';

    public string $period = 'this_month';

    /**
     * Configures period label for the Filament administration interface.
     */
    public function periodLabel(): string
    {
        return match ($this->period) {
            'today' => 'Today',
            'this_week' => 'This week',
            'this_quarter' => 'This quarter',
            'this_year' => 'This year',
            'all' => 'All time',
            default => 'This month',
        };
    }

    /**
     * Configures report for the Filament administration interface.
     */
    public function report(): array
    {
        [$periodStart, $periodEnd] = $this->periodBounds();

        $roomStatus = [
            'total' => Room::count(),
            'occupied' => Room::where('status', 'occupied')->count(),
            'available' => Room::where('status', 'available')->count(),
            'maintenance' => Room::where('status', 'maintenance')->count(),
        ];

        $roomsInService = $roomStatus['total'] - $roomStatus['maintenance'];

        $hotelBookings = Booking::query()
            ->whereDate('check_in', '<', $periodEnd->toDateString())
            ->whereDate('check_out', '>', $periodStart->toDateString())
            ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
            ->get(['id', 'check_in', 'check_out']);

        $bookedRoomNights = $hotelBookings->sum(function (Booking $booking) use ($periodStart, $periodEnd): int {
            $checkIn = Carbon::parse($booking->check_in)->max($periodStart);
            $checkOut = Carbon::parse($booking->check_out)->min($periodEnd);

            return max(0, $checkIn->diffInDays($checkOut));
        });

        $periodDays = max(1, (int) $periodStart->diffInDays($periodEnd));
        $roomNightCapacity = $roomsInService * $periodDays;

        $conferenceBookings = ConferenceBooking::query()
            ->whereBetween('booking_date', [$periodStart->toDateString(), $periodEnd->copy()->subDay()->toDateString()])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        $tableReservations = RestaurantReservation::query()
            ->whereBetween('reservation_date', [$periodStart->toDateString(), $periodEnd->copy()->subDay()->toDateString()])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        $conferenceAvailability = [
            'available' => ConferenceRoom::where('is_available', true)->count(),
            'unavailable' => ConferenceRoom::where('is_available', false)->count(),
        ];

        $tableStatus = [
            'available' => RestaurantTable::where('status', 'available')->count(),
            'reserved' => RestaurantTable::where('status', 'reserved')->count(),
            'occupied' => RestaurantTable::where('status', 'occupied')->count(),
            'unavailable' => RestaurantTable::whereIn('status', ['cleaning', 'maintenance'])->count(),
        ];

        return [
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'roomStatus' => $roomStatus,
            'roomsInService' => $roomsInService,
            'hotelBookings' => $hotelBookings->count(),
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
     * Configures period bounds for the Filament administration interface.
     */
    private function periodBounds(): array
    {
        return match ($this->period) {
            'today' => [today()->startOfDay(), today()->addDay()->startOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()->addDay()->startOfDay()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()->addDay()->startOfDay()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()->addDay()->startOfDay()],
            'all' => $this->allTimeBounds(),
            default => [now()->startOfMonth(), now()->endOfMonth()->addDay()->startOfDay()],
        };
    }

    /**
     * Configures all time bounds for the Filament administration interface.
     */
    private function allTimeBounds(): array
    {
        $start = collect([
            Booking::min('check_in'),
            ConferenceBooking::min('booking_date'),
            RestaurantReservation::min('reservation_date'),
        ])->filter()->min() ?? today()->toDateString();

        $end = collect([
            Booking::max('check_out'),
            ConferenceBooking::max('booking_date'),
            RestaurantReservation::max('reservation_date'),
        ])->filter()->max() ?? today()->toDateString();

        return [
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->addDay()->startOfDay(),
        ];
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'receptionist']) ?? false;
    }
}
