<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\SuperAdminDashboard;
use App\Filament\Admin\Widgets\CorporateBillingOverview;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use App\Filament\Admin\Widgets\RoleDashboardOverview;
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

    public function test_command_center_and_executive_stats_are_prioritized_above_super_admin_dashboard_sections(): void
    {
        [$priorityWidgets] = $this->dashboardWidgetLayout();

        self::assertSame([
            RoleDashboardOverview::class,
            SuperAdminStats::class,
        ], $priorityWidgets);
    }

    public function test_specialized_super_admin_stats_start_their_domain_sections(): void
    {
        [, $sections] = $this->dashboardWidgetLayout();

        self::assertSame(SuperAdminOperationsStats::class, $sections['Operations'][0]);
        self::assertSame(SuperAdminFinanceStats::class, $sections['Finance'][0]);
        self::assertSame(KitchenStockStats::class, $sections['Kitchen'][0]);
    }

    public function test_restaurant_revenue_chart_is_grouped_with_restaurant_widgets(): void
    {
        [, $sections] = $this->dashboardWidgetLayout();

        self::assertContains(RestaurantRevenueChart::class, $sections['Restaurant']);
        self::assertNotContains(RestaurantRevenueChart::class, $sections['Finance']);
    }

    public function test_non_restaurant_financial_widgets_remain_in_the_finance_section(): void
    {
        [, $sections] = $this->dashboardWidgetLayout();

        self::assertContains(SuperAdminFinanceStats::class, $sections['Finance']);
        self::assertContains(CorporateBillingOverview::class, $sections['Finance']);
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
