<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\SuperAdminDashboard;
use Filament\Widgets\StatsOverviewWidget;
use Tests\TestCase;

class SuperAdminDashboardStatsTest extends TestCase
{
    public function test_super_admin_dashboard_registers_summary_stats_before_detail_widgets(): void
    {
        $widgets = (new SuperAdminDashboard)->getWidgets();

        $operationsStats = 'App\\Filament\\Admin\\Widgets\\SuperAdminOperationsStats';
        $financeStats = 'App\\Filament\\Admin\\Widgets\\SuperAdminFinanceStats';

        self::assertContains($operationsStats, $widgets);
        self::assertContains($financeStats, $widgets);
        self::assertTrue(is_subclass_of($operationsStats, StatsOverviewWidget::class));
        self::assertTrue(is_subclass_of($financeStats, StatsOverviewWidget::class));

        $firstDetailWidget = array_search(
            'App\\Filament\\Admin\\Widgets\\RestaurantRevenueChart',
            $widgets,
            true,
        );
        self::assertIsInt($firstDetailWidget);
        self::assertLessThan($firstDetailWidget, array_search($operationsStats, $widgets, true));
        self::assertLessThan($firstDetailWidget, array_search($financeStats, $widgets, true));
    }
}
