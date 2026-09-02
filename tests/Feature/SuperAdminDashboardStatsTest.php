<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\SuperAdminDashboard;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\SuperAdminFinanceStats;
use App\Filament\Admin\Widgets\SuperAdminOperationsStats;
use App\Filament\Admin\Widgets\SuperAdminStats;
use Filament\Widgets\StatsOverviewWidget;
use ReflectionMethod;
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

    public function test_only_executive_stats_are_prioritized_above_super_admin_dashboard_sections(): void
    {
        [$priorityWidgets] = $this->dashboardWidgetLayout();

        self::assertSame([SuperAdminStats::class], $priorityWidgets);
    }

    public function test_specialized_super_admin_stats_start_their_domain_sections(): void
    {
        [, $sections] = $this->dashboardWidgetLayout();

        self::assertSame(SuperAdminOperationsStats::class, $sections['Operations'][0]);
        self::assertSame(SuperAdminFinanceStats::class, $sections['Finance'][0]);
        self::assertSame(KitchenStockStats::class, $sections['Kitchen'][0]);
    }

    /**
     * @return array{0: array<int, mixed>, 1: array<string, array<int, mixed>>}
     */
    private function dashboardWidgetLayout(): array
    {
        $dashboard = new SuperAdminDashboard;
        $method = new ReflectionMethod($dashboard, 'dashboardWidgetLayout');
        $method->setAccessible(true);

        return $method->invoke($dashboard);
    }
}
