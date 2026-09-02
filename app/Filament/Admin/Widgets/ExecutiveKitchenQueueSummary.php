<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\BuildsDashboardDrillDowns;
use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\RestaurantOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a read-only kitchen queue summary for executive dashboards.
 */
class ExecutiveKitchenQueueSummary extends StatsOverviewWidget
{
    use BuildsDashboardDrillDowns;
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * Builds the queue summary statistics.
     */
    protected function getStats(): array
    {
        $counts = $this->forDashboardDateRange(RestaurantOrder::kitchenQueue())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $periodLabel = $this->dashboardDateRangeLabel();

        return [
            $this->drillDown(
                Stat::make('Total active queue', number_format($counts->sum()))
                    ->description($periodLabel)
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary'),
                $this->restaurantOrderDrillDownUrl('kitchen_queue'),
            ),
            $this->drillDown(
                Stat::make('Waiting to start', number_format((int) ($counts['confirmed'] ?? 0)))
                    ->description($periodLabel)
                    ->icon('heroicon-o-clock')
                    ->color('info'),
                $this->restaurantOrderDrillDownUrl('confirmed'),
            ),
            $this->drillDown(
                Stat::make('Preparing', number_format((int) ($counts['preparing'] ?? 0)))
                    ->description($periodLabel)
                    ->icon('heroicon-o-fire')
                    ->color('warning'),
                $this->restaurantOrderDrillDownUrl('preparing'),
            ),
            $this->drillDown(
                Stat::make('Ready to serve', number_format((int) ($counts['ready'] ?? 0)))
                    ->description($periodLabel)
                    ->icon('heroicon-o-bell-alert')
                    ->color('success'),
                $this->restaurantOrderDrillDownUrl('ready'),
            ),
        ];
    }

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen dashboard') ?? false;
    }
}
