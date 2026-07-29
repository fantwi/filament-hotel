<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuperAdminStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view super admin dashboard') ?? false;
    }

    protected function getStats(): array
    {
        $revenue = Payment::whereIn('payment_status', ['paid', 'completed'])->sum('amount');
        $reservations = Booking::count() + ConferenceBooking::count() + RestaurantReservation::count();

        return [
            Stat::make('System Users', number_format(User::count()))->icon('heroicon-o-users')->color('primary'),
            Stat::make('Registered Guests', number_format(Guest::count()))->icon('heroicon-o-user-group')->color('info'),
            Stat::make('All Reservations', number_format($reservations))->icon('heroicon-o-calendar-days')->color('warning'),
            Stat::make('Restaurant Orders', number_format(RestaurantOrder::count()))->icon('heroicon-o-shopping-bag')->color('gray'),
            Stat::make('Total Revenue', 'GHS '.number_format($revenue, 2))->icon('heroicon-o-banknotes')->color('success'),
        ];
    }
}
