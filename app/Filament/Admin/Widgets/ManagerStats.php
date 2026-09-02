<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\BuildsDashboardDrillDowns;
use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the manager stats Filament dashboard widget.
 */
class ManagerStats extends StatsOverviewWidget
{
    use BuildsDashboardDrillDowns;
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view manager dashboard') ?? false;
    }

    /**
     * Builds and returns stats.
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();

        return [
            $this->drillDown(
                Stat::make('Hotel Arrivals', Booking::query()->whereNotIn('status', ['cancelled', 'expired', 'no_show'])->whereBetween('check_in', [$start->toDateString(), $end->toDateString()])->count())->description($this->dashboardDateRangeLabel())->color('primary'),
                $this->bookingArrivalsDrillDownUrl(),
            ),
            $this->drillDown(
                Stat::make('Conference Events', ConferenceBooking::query()->where('status', '!=', 'cancelled')->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])->count())->description($this->dashboardDateRangeLabel())->color('info'),
                $this->conferenceEventsDrillDownUrl(),
            ),
            $this->drillDown(
                Stat::make('Restaurant Reservations', RestaurantReservation::query()->whereNotIn('status', ['cancelled', 'no_show'])->whereBetween('reservation_date', [$start->toDateString(), $end->toDateString()])->count())->description($this->dashboardDateRangeLabel())->color('warning'),
                $this->restaurantReservationActivityDrillDownUrl(),
            ),
            $this->drillDown(
                Stat::make('Food Orders', $this->forDashboardDateRange(RestaurantOrder::query()->where('status', '!=', 'cancelled'))->count())->description('Created in selected range')->color('success'),
                $this->restaurantOrderDrillDownUrl(),
            ),
        ];
    }
}
