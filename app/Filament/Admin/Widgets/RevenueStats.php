<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRevenue = Payment::sum('amount');

        $todayRevenue = Payment::whereDate('created_at', today())
            ->sum('amount');

        $monthlyRevenue = Payment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $outstandingBalance = Booking::get()
            ->sum(fn ($booking) => $booking->balance);

        $totalBookings = Booking::count();

        return [
            //
            Stat::make('Total Revenue', 'GHS '.number_format($totalRevenue, 2))
                ->description('All-time payments')
                ->color('success'),

            Stat::make('Revenue Today', 'GHS '.number_format($todayRevenue, 2))
                ->description(now()->format('d M Y'))
                ->color('primary'),

            Stat::make('Revenue This Month', 'GHS '.number_format($monthlyRevenue, 2))
                ->description(now()->format('F Y'))
                ->color('info'),

            Stat::make('Outstanding Balance', 'GHS '.number_format($outstandingBalance, 2))
                ->description('Unpaid bookings')
                ->color('danger'),

            // Stat::make('Total Bookings', $totalBookings)
            //     ->description('All bookings')
            //     ->color('gray'),
        ];
    }

    // Only Admins and Accountants can view Revenue Stats Widget
    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin', 'accountant']);
    }
}
