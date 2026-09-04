<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Services\KitchenProductionReportService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the kitchen production stats Filament dashboard widget.
 */
class KitchenProductionStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen production reports') ?? false;
    }

    /**
     * Builds and returns stats.
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $r = app(KitchenProductionReportService::class)->build($start, $end)['summary'];

        return [
            Stat::make('Tracked Food Items', $r['tracked_items'])->description($this->dashboardDateRangeLabel())->color('primary'),
            Stat::make('Low finished-food balances', $r['low_stock_items'])->description('At or below threshold at period end')->color('warning'),
            Stat::make('Negative Variances', $r['negative_variance_items'])->description('Production compared with sales')->color('danger'),
            Stat::make('Food Revenue', 'GHS '.number_format($r['sales_revenue'], 2))->description($this->dashboardDateRangeLabel())->color('success'),
        ];
    }
}
