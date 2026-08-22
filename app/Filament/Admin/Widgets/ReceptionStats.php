<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the reception stats Filament dashboard widget.
 */
class ReceptionStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view reception dashboard') ?? false;
    }

    /**
     * Builds and returns stats.
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $from = $start->toDateString();
        $until = $end->toDateString();

        return [
            Stat::make('Hotel Arrivals', Booking::query()->whereBetween('check_in', [$from, $until])->whereIn('status', ['confirmed', 'pending'])->count())->description($this->dashboardDateRangeLabel())->icon('heroicon-o-arrow-right-end-on-rectangle')->color('primary'),
            Stat::make('Hotel Departures', Booking::query()->whereBetween('check_out', [$from, $until])->whereIn('status', ['confirmed', 'checked_in'])->count())->description($this->dashboardDateRangeLabel())->icon('heroicon-o-arrow-left-start-on-rectangle')->color('warning'),
            Stat::make('Conference Bookings', ConferenceBooking::query()->whereBetween('booking_date', [$from, $until])->count())->description($this->dashboardDateRangeLabel())->icon('heroicon-o-building-office')->color('info'),
            Stat::make('Restaurant Reservations', RestaurantReservation::query()->whereBetween('reservation_date', [$from, $until])->count())->description($this->dashboardDateRangeLabel())->icon('heroicon-o-calendar-days')->color('success'),
        ];
    }
}
