<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManagerPeriodReport extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view manager dashboard') ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Daily Activity', $this->activityFor(today()->startOfDay()))->description('Today')->icon('heroicon-o-calendar-days')->color('primary'),
            Stat::make('Weekly Activity', $this->activityFor(today()->startOfWeek()))->description('Since Monday')->icon('heroicon-o-calendar')->color('info'),
            Stat::make('Monthly Activity', $this->activityFor(today()->startOfMonth()))->description(now()->format('F Y'))->icon('heroicon-o-chart-bar')->color('success'),
            Stat::make('Quarterly Activity', $this->activityFor(today()->startOfQuarter()))->description('Current quarter')->icon('heroicon-o-presentation-chart-line')->color('warning'),
            Stat::make('Annual Activity', $this->activityFor(today()->startOfYear()))->description('Year to date')->icon('heroicon-o-trophy')->color('gray'),
        ];
    }

    private function activityFor(Carbon $from): int
    {
        return Booking::whereBetween('created_at', [$from, now()])->count()
            + ConferenceBooking::whereBetween('created_at', [$from, now()])->count()
            + RestaurantReservation::whereBetween('created_at', [$from, now()])->count()
            + RestaurantOrder::whereBetween('created_at', [$from, now()])->count();
    }
}
