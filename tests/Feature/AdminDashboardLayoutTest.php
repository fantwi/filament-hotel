<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\AdminDashboard;
use App\Filament\Admin\Widgets\AdminFinanceStats;
use App\Filament\Admin\Widgets\AdminServiceStats;
use App\Filament\Admin\Widgets\CorporateBillingOverview;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\ManagerOperationsChart;
use App\Filament\Admin\Widgets\RecentPayments;
use App\Filament\Admin\Widgets\RoleDashboardOverview;
use ReflectionMethod;
use Tests\TestCase;

class AdminDashboardLayoutTest extends TestCase
{
    public function test_command_center_metrics_are_prioritized_and_domain_widgets_are_grouped(): void
    {
        [$priorityWidgets, $sections] = $this->dashboardWidgetLayout();

        self::assertSame([
            RoleDashboardOverview::class,
            AdminServiceStats::class,
            AdminFinanceStats::class,
        ], $priorityWidgets);
        self::assertSame([
            'Operations' => [
                ManagerOperationsChart::class,
            ],
            'Finance' => [
                CorporateBillingOverview::class,
                RecentPayments::class,
            ],
            'Kitchen' => [
                KitchenStockStats::class,
                KitchenOrderQueue::class,
            ],
        ], $sections);

        $allWidgets = [...$priorityWidgets, ...array_merge(...array_values($sections))];

        self::assertCount(count(array_unique($allWidgets)), $allWidgets);
    }

    public function test_dashboard_preserves_the_responsive_three_column_grid(): void
    {
        self::assertSame([
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ], (new AdminDashboard)->getColumns());
    }

    /**
     * @return array{0: array<int, class-string>, 1: array<string, array<int, class-string>>}
     */
    private function dashboardWidgetLayout(): array
    {
        $dashboard = new AdminDashboard;
        $method = new ReflectionMethod($dashboard, 'dashboardWidgetLayout');
        $method->setAccessible(true);

        return $method->invoke($dashboard);
    }
}
