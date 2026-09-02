<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\ConferenceBooking;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the reception stats Filament dashboard widget.
 */
class ReceptionStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Venue activity';

    protected int|array|null $columns = 2;

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view reception dashboard') ?? false;
    }

    /**
     * Builds and returns stats.
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $from = $start->toDateString();
        $until = $end->toDateString();

        return [
            Stat::make(
                'Conference events',
                ConferenceBooking::query()
                    ->whereBetween('booking_date', [$from, $until])
                    ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
                    ->count(),
            )->description($this->dashboardDateRangeLabel())->icon('heroicon-o-building-office')->color('info'),
            Stat::make(
                'Table reservations',
                RestaurantReservation::query()
                    ->whereBetween('reservation_date', [$from, $until])
                    ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
                    ->count(),
            )->description($this->dashboardDateRangeLabel())->icon('heroicon-o-calendar-days')->color('success'),
        ];
    }
}
