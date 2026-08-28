<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\ManagerDashboard;
use App\Filament\Admin\Widgets\ManagerOperationsStats;
use Filament\Widgets\StatsOverviewWidget;
use Tests\TestCase;

class ManagerDashboardStatsTest extends TestCase
{
    public function test_manager_dashboard_registers_distinct_operations_stats_before_detail_widgets(): void
    {
        $widgets = (new ManagerDashboard)->getWidgets();

        $activityStats = 'App\\Filament\\Admin\\Widgets\\ManagerStats';
        $operationsStats = 'App\\Filament\\Admin\\Widgets\\ManagerOperationsStats';
        $periodReport = 'App\\Filament\\Admin\\Widgets\\ManagerPeriodReport';
        $detailWidgets = [
            'App\\Filament\\Admin\\Widgets\\CorporateBillingOverview',
            'App\\Filament\\Admin\\Widgets\\KitchenProductionStats',
            'App\\Filament\\Admin\\Widgets\\KitchenStockStats',
            'App\\Filament\\Admin\\Widgets\\ManagerOperationsChart',
            'App\\Filament\\Admin\\Widgets\\RestaurantOrderStatusChart',
            'App\\Filament\\Admin\\Widgets\\KitchenOrderQueue',
        ];

        self::assertContains($activityStats, $widgets);
        self::assertContains($operationsStats, $widgets);
        self::assertNotContains($periodReport, $widgets);
        self::assertTrue(is_subclass_of($activityStats, StatsOverviewWidget::class));
        self::assertTrue(is_subclass_of($operationsStats, StatsOverviewWidget::class));
        self::assertContains(InteractsWithDashboardDateRange::class, class_uses_recursive(ManagerOperationsStats::class));

        $activityIndex = array_search($activityStats, $widgets, true);
        $operationsIndex = array_search($operationsStats, $widgets, true);
        self::assertIsInt($activityIndex);
        self::assertIsInt($operationsIndex);

        foreach ($detailWidgets as $detailWidget) {
            $detailIndex = array_search($detailWidget, $widgets, true);
            self::assertIsInt($detailIndex);
            self::assertLessThan($detailIndex, $activityIndex);
            self::assertLessThan($detailIndex, $operationsIndex);
        }
    }
}
