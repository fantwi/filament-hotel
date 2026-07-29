<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountantStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view accountant dashboard') ?? false;
    }

    protected function getStats(): array
    {
        $revenue = Payment::whereIn('payment_status', ['paid', 'completed'])->sum('amount');
        $outstanding = Booking::whereIn('payment_status', ['pending', 'unpaid'])->sum('total_price')
            + ConferenceBooking::where('payment_status', 'pending')->sum('total_price')
            + RestaurantReservation::where('payment_status', 'pending')->sum('reservation_fee')
            + RestaurantOrder::where('payment_status', 'pending')->where('status', '!=', 'cancelled')->sum('total');

        return [
            Stat::make('Completed Revenue', 'GHS '.number_format($revenue, 2))->icon('heroicon-o-banknotes')->color('success'),
            Stat::make('Outstanding Balance', 'GHS '.number_format($outstanding, 2))->icon('heroicon-o-clock')->color('warning'),
            Stat::make('Refunded', 'GHS '.number_format(Payment::whereIn('payment_status', ['refunded', 'refund'])->sum('amount'), 2))->icon('heroicon-o-arrow-uturn-left')->color('danger'),
            Stat::make('Payments Today', number_format(Payment::whereDate('created_at', today())->count()))->icon('heroicon-o-credit-card')->color('info'),
        ];
    }
}
