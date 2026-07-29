<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReceptionStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view reception dashboard') ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Hotel Arrivals Today', Booking::whereDate('check_in', today())->whereIn('status', ['confirmed', 'pending'])->count())->icon('heroicon-o-arrow-right-end-on-rectangle')->color('primary'),
            Stat::make('Hotel Departures Today', Booking::whereDate('check_out', today())->whereIn('status', ['confirmed', 'checked_in'])->count())->icon('heroicon-o-arrow-left-start-on-rectangle')->color('warning'),
            Stat::make('Conference Bookings Today', ConferenceBooking::whereDate('booking_date', today())->count())->icon('heroicon-o-building-office')->color('info'),
            Stat::make('Restaurant Reservations Today', RestaurantReservation::whereDate('reservation_date', today())->count())->icon('heroicon-o-calendar-days')->color('success'),
        ];
    }
}
