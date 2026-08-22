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
 * Provides the manager stats Filament dashboard widget.
 */
class ManagerStats extends StatsOverviewWidget
{
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
            Stat::make('Hotel Arrivals', Booking::query()->whereBetween('check_in', [$start->toDateString(), $end->toDateString()])->count())->description($this->dashboardDateRangeLabel())->color('primary'),
            Stat::make('Conference Events', ConferenceBooking::query()->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])->count())->description($this->dashboardDateRangeLabel())->color('info'),
            Stat::make('Restaurant Reservations', RestaurantReservation::query()->whereBetween('reservation_date', [$start->toDateString(), $end->toDateString()])->count())->description($this->dashboardDateRangeLabel())->color('warning'),
            Stat::make('Food Orders', $this->forDashboardDateRange(RestaurantOrder::query())->count())->description('Created in selected range')->color('success'),
            Stat::make('Active Kitchen Orders', $this->forDashboardDateRange(RestaurantOrder::query())->whereIn('status', ['confirmed', 'preparing', 'ready'])->count())->description('Created in selected range')->color('danger'),
        ];
    }
}
