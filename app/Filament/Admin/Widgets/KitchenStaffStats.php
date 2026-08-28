<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\RestaurantOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a task-focused summary for kitchen staff.
 */
class KitchenStaffStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return (auth()->user()?->hasRole('kitchen_staff') ?? false)
            && (auth()->user()?->can('view kitchen dashboard') ?? false);
    }

    /**
     * Builds date-filtered kitchen task stats.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $periodLabel = $this->dashboardDateRangeLabel();

        return [
            Stat::make('Orders waiting to start', number_format($this->ordersInStatus('confirmed')))
                ->description($periodLabel)
                ->icon('heroicon-o-clock')
                ->color('info'),
            Stat::make('Orders preparing', number_format($this->ordersInStatus('preparing')))
                ->description($periodLabel)
                ->icon('heroicon-o-fire')
                ->color('warning'),
            Stat::make('Orders ready to serve', number_format($this->ordersInStatus('ready')))
                ->description($periodLabel)
                ->icon('heroicon-o-bell-alert')
                ->color('success'),
            Stat::make('Orders served', number_format($this->forDashboardDateRange(RestaurantOrder::query(), 'served_at')->where('status', 'served')->count()))
                ->description($periodLabel)
                ->icon('heroicon-o-check-circle')
                ->color('primary'),
        ];
    }

    /**
     * Counts eligible kitchen orders in the selected period by status.
     */
    private function ordersInStatus(string $status): int
    {
        return $this->forDashboardDateRange(RestaurantOrder::kitchenQueue())
            ->where('status', $status)
            ->count();
    }
}
