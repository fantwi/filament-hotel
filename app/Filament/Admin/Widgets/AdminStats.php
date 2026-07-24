<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';
    public static function canView(): bool { return auth()->user()?->can('view admin dashboard') ?? false; }
    protected function getStats(): array
    {
        return [
            Stat::make('Active Hotel Bookings', Booking::whereIn('status', ['pending', 'confirmed', 'checked_in'])->count())->color('primary'),
            Stat::make('Conference Bookings', ConferenceBooking::whereIn('status', ['pending', 'confirmed'])->count())->color('info'),
            Stat::make('Restaurant Reservations', RestaurantReservation::whereIn('status', ['pending', 'confirmed', 'checked_in'])->count())->color('warning'),
            Stat::make('Kitchen Orders', RestaurantOrder::whereIn('status', ['confirmed', 'preparing', 'ready'])->count())->color('success'),
        ];
    }
}
