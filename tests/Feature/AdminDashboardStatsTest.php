<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\AdminDashboard;
use Filament\Widgets\StatsOverviewWidget;
use Tests\TestCase;

class AdminDashboardStatsTest extends TestCase
{
    public function test_admin_dashboard_registers_service_and_finance_stats_before_detail_widgets(): void
    {
        $widgets = (new AdminDashboard)->getWidgets();

        $serviceStats = 'App\\Filament\\Admin\\Widgets\\AdminServiceStats';
        $financeStats = 'App\\Filament\\Admin\\Widgets\\AdminFinanceStats';

        self::assertContains($serviceStats, $widgets);
        self::assertContains($financeStats, $widgets);
        self::assertTrue(is_subclass_of($serviceStats, StatsOverviewWidget::class));
        self::assertTrue(is_subclass_of($financeStats, StatsOverviewWidget::class));

        $firstDetailWidget = array_search(
            'App\\Filament\\Admin\\Widgets\\CorporateBillingOverview',
            $widgets,
            true,
        );
        self::assertIsInt($firstDetailWidget);
        self::assertLessThan($firstDetailWidget, array_search($serviceStats, $widgets, true));
        self::assertLessThan($firstDetailWidget, array_search($financeStats, $widgets, true));
    }
}
