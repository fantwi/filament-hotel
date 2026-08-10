<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view admin dashboard') ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Active Hotel Bookings', $this->forDashboardDateRange(Booking::query())->whereIn('status', ['pending', 'confirmed', 'checked_in'])->count())->description($this->dashboardDateRangeLabel())->color('primary'),
            Stat::make('Conference Bookings', $this->forDashboardDateRange(ConferenceBooking::query())->whereIn('status', ['pending', 'confirmed'])->count())->description($this->dashboardDateRangeLabel())->color('info'),
            Stat::make('Restaurant Reservations', $this->forDashboardDateRange(RestaurantReservation::query())->whereIn('status', ['pending', 'confirmed', 'checked_in'])->count())->description($this->dashboardDateRangeLabel())->color('warning'),
            Stat::make('Kitchen Orders', $this->forDashboardDateRange(RestaurantOrder::query())->whereIn('status', ['confirmed', 'preparing', 'ready'])->count())->description($this->dashboardDateRangeLabel())->color('success'),
        ];
    }
}
