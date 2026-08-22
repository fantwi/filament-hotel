<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\RestaurantReservation;
use App\Models\Room;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the hotel statistics Filament dashboard widget.
 */
class HotelStatistics extends StatsOverviewWidget
{
    /**
     * Builds and returns stats.
     */
    protected function getStats(): array
    {
        $totalBookings = Booking::count()
            + ConferenceBooking::count()
            + RestaurantReservation::count();

        $totalRevenue = Payment::query()
            ->whereIn('payment_status', ['paid', 'completed'])
            ->sum('amount');

        $outstandingBalance = Booking::query()
            ->whereIn('payment_status', ['pending', 'unpaid'])
            ->sum('total_price')
            + ConferenceBooking::query()
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->sum('total_price')
            + RestaurantReservation::query()
                ->where('payment_status', 'pending')
                ->sum('reservation_fee');

        $totalRooms = Room::count();
        $occupancy = $totalRooms > 0
            ? round((Room::where('status', 'occupied')->count() / $totalRooms) * 100, 1)
            : 0;

        return [
            Stat::make('Total Bookings', number_format($totalBookings))
                ->description('Hotel, conference & restaurant')
                ->color('primary')
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Revenue', 'GHS '.number_format($totalRevenue, 2))
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Outstanding', 'GHS '.number_format($outstandingBalance, 2))
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make('Guests', number_format(Guest::count()))
                ->color('info')
                ->icon('heroicon-o-users'),
            Stat::make('Occupancy', $occupancy.'%')
                ->color($occupancy >= 80 ? 'danger' : 'success')
                ->icon('heroicon-o-home'),
        ];
    }

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
