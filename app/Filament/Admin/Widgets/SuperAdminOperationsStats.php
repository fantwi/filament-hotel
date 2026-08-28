<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\Room;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the super-admin operations stats overview widget.
 */
class SuperAdminOperationsStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view super admin dashboard') ?? false;
    }

    /**
     * Builds operational stats for the selected dashboard period.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $periodLabel = $this->dashboardDateRangeLabel();

        return [
            Stat::make(
                'Active Hotel Bookings',
                number_format($this->forDashboardDateRange(Booking::query())->whereIn('status', ['pending', 'confirmed', 'checked_in'])->count()),
            )
                ->description($periodLabel)
                ->icon('heroicon-o-home-modern')
                ->color('primary'),
            Stat::make(
                'Conference Bookings',
                number_format($this->forDashboardDateRange(ConferenceBooking::query())->whereIn('status', ['pending', 'confirmed'])->count()),
            )
                ->description($periodLabel)
                ->icon('heroicon-o-building-office-2')
                ->color('info'),
            Stat::make(
                'Table Reservations',
                number_format($this->forDashboardDateRange(RestaurantReservation::query())->whereIn('status', ['pending', 'confirmed', 'checked_in'])->count()),
            )
                ->description($periodLabel)
                ->icon('heroicon-o-rectangle-stack')
                ->color('warning'),
            Stat::make(
                'Kitchen Queue',
                number_format($this->forDashboardDateRange(RestaurantOrder::query())->whereIn('status', ['confirmed', 'preparing', 'ready'])->count()),
            )
                ->description($periodLabel)
                ->icon('heroicon-o-fire')
                ->color('success'),
            Stat::make('Occupied Rooms', number_format(Room::query()->where('status', 'occupied')->count()))
                ->description('Current property snapshot')
                ->icon('heroicon-o-key')
                ->color('danger'),
        ];
    }
}
