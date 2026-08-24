<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the manager period report Filament dashboard widget.
 */
class ManagerPeriodReport extends StatsOverviewWidget
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
        $hotel = $this->forDashboardDateRange(Booking::query())->count();
        $conference = $this->forDashboardDateRange(ConferenceBooking::query())->count();
        $reservations = $this->forDashboardDateRange(RestaurantReservation::query())->count();
        $orders = $this->forDashboardDateRange(RestaurantOrder::query())->count();

        return [
            Stat::make('Total Activity', number_format($hotel + $conference + $reservations + $orders))->description($this->dashboardDateRangeLabel())->icon('heroicon-o-chart-bar')->color('primary'),
            Stat::make('Hotel Bookings', number_format($hotel))->description('Created in selected range')->icon('heroicon-o-home')->color('info'),
            Stat::make('Conference Events', number_format($conference))->description('Created in selected range')->icon('heroicon-o-building-office')->color('success'),
            Stat::make('Restaurant Activity', number_format($reservations + $orders))->description('Reservations and orders')->icon('heroicon-o-shopping-bag')->color('warning'),
        ];
    }
}
