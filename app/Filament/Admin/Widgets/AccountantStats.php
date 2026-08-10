<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountantStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view accountant dashboard') ?? false;
    }

    protected function getStats(): array
    {
        $revenue = $this->forDashboardDateRange(Payment::query())->whereIn('payment_status', ['paid', 'completed'])->sum('amount');
        $outstanding = $this->forDashboardDateRange(Booking::query())->whereIn('payment_status', ['pending', 'unpaid'])->sum('total_price')
            + $this->forDashboardDateRange(ConferenceBooking::query())->where('payment_status', 'pending')->sum('total_price')
            + $this->forDashboardDateRange(RestaurantReservation::query())->where('payment_status', 'pending')->sum('reservation_fee')
            + $this->forDashboardDateRange(RestaurantOrder::query())->where('payment_status', 'pending')->where('status', '!=', 'cancelled')->sum('total');
        $refunds = $this->forDashboardDateRange(Payment::query())->whereIn('payment_status', ['refunded', 'refund'])->sum('amount');
        $payments = $this->forDashboardDateRange(Payment::query())->count();

        return [
            Stat::make('Completed Revenue', 'GHS '.number_format($revenue, 2))->description($this->dashboardDateRangeLabel())->icon('heroicon-o-banknotes')->color('success'),
            Stat::make('Outstanding Balance', 'GHS '.number_format($outstanding, 2))->description('Created in selected range')->icon('heroicon-o-clock')->color('warning'),
            Stat::make('Refunded', 'GHS '.number_format($refunds, 2))->description($this->dashboardDateRangeLabel())->icon('heroicon-o-arrow-uturn-left')->color('danger'),
            Stat::make('Payments Received', number_format($payments))->description($this->dashboardDateRangeLabel())->icon('heroicon-o-credit-card')->color('info'),
        ];
    }
}
