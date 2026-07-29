<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\Room;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManagerStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view manager dashboard') ?? false;
    }

    protected function getStats(): array
    {
        $rooms = Room::count();
        $occupancy = $rooms ? round(Room::where('status', 'occupied')->count() / $rooms * 100, 1) : 0;

        return [
            Stat::make('Room Occupancy', $occupancy.'%')->color('success'),
            Stat::make('Arrivals Today', Booking::whereDate('check_in', today())->count())->color('primary'),
            Stat::make('Conference Events Today', ConferenceBooking::whereDate('booking_date', today())->count())->color('info'),
            Stat::make('Restaurant Reservations Today', RestaurantReservation::whereDate('reservation_date', today())->count())->color('warning'),
            Stat::make('Active Kitchen Orders', RestaurantOrder::whereIn('status', ['confirmed', 'preparing', 'ready'])->count())->color('danger'),
        ];
    }
}
