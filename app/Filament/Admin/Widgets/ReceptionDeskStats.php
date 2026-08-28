<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides actionable front-desk stats for the reception dashboard.
 */
class ReceptionDeskStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view reception dashboard') ?? false;
    }

    /**
     * Builds date-filtered front-desk action stats.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $from = $start->toDateString();
        $until = $end->toDateString();
        $periodLabel = $this->dashboardDateRangeLabel();

        $activeStays = Booking::query()
            ->where('status', 'checked_in')
            ->whereDate('check_in', '<=', $until)
            ->whereDate('check_out', '>=', $from)
            ->count();
        $pendingArrivals = Booking::query()
            ->whereBetween('check_in', [$from, $until])
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
        $pendingDepartures = Booking::query()
            ->whereBetween('check_out', [$from, $until])
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();
        $unpaidArrivals = Booking::query()
            ->whereBetween('check_in', [$from, $until])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereIn('payment_status', ['pending', 'unpaid'])
            ->sum('total_price');

        return [
            Stat::make('Guests currently checked in', number_format($activeStays))
                ->description($periodLabel)
                ->icon('heroicon-o-user-group')
                ->color('success'),
            Stat::make('Pending arrivals', number_format($pendingArrivals))
                ->description($periodLabel)
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->color('primary'),
            Stat::make('Pending departures', number_format($pendingDepartures))
                ->description($periodLabel)
                ->icon('heroicon-o-arrow-left-start-on-rectangle')
                ->color('warning'),
            Stat::make('Unpaid arrival balance', 'GHS '.number_format((float) $unpaidArrivals, 2))
                ->description('Requires payment follow-up')
                ->icon('heroicon-o-credit-card')
                ->color('danger'),
        ];
    }
}
