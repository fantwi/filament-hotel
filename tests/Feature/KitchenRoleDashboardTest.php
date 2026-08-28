<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\KitchenManagerDashboard;
use App\Filament\Admin\Pages\Dashboards\KitchenStaffDashboard;
use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use App\Filament\Admin\Widgets\KitchenManagerStats;
use App\Filament\Admin\Widgets\KitchenStaffStats;
use Filament\Widgets\StatsOverviewWidget;
use Tests\TestCase;

class KitchenRoleDashboardTest extends TestCase
{
    public function test_kitchen_dashboards_use_shared_filters_and_role_specific_stats_first(): void
    {
        foreach ([
            KitchenManagerDashboard::class => [KitchenManagerStats::class, 'KitchenOrderQueue', 'KitchenProductionStats', 'KitchenStockStats'],
            KitchenStaffDashboard::class => [KitchenStaffStats::class, 'KitchenOrderQueue', 'KitchenProductionStats'],
        ] as $dashboardClass => $widgetConfig) {
            $statsClass = $widgetConfig[0];
            $detailNames = array_slice($widgetConfig, 1);

            self::assertTrue(is_subclass_of($dashboardClass, TimeFilteredDashboard::class));
            self::assertSame(['default' => 1, 'md' => 2, 'xl' => 3], (new $dashboardClass)->getColumns());
            self::assertSame($statsClass, (new $dashboardClass)->getWidgets()[0]);
            self::assertTrue(is_subclass_of($statsClass, StatsOverviewWidget::class));
            self::assertContains(InteractsWithDashboardDateRange::class, class_uses_recursive($statsClass));

            $widgets = (new $dashboardClass)->getWidgets();
            foreach ($detailNames as $detailName) {
                $detailClass = 'App\\Filament\\Admin\\Widgets\\'.$detailName;
                self::assertContains($detailClass, $widgets);
                self::assertLessThan(array_search($detailClass, $widgets, true), array_search($statsClass, $widgets, true));
            }
        }
    }

    public function test_role_dashboard_routes_kitchen_roles_to_separate_pages(): void
    {
        $source = file_get_contents(app_path('Filament/Admin/Pages/Dashboards/RoleDashboard.php'));

        self::assertStringContainsString("hasRole('kitchen_manager')", $source);
        self::assertStringContainsString('KitchenManagerDashboard::class', $source);
        self::assertStringContainsString("hasRole('kitchen_staff')", $source);
        self::assertStringContainsString('KitchenStaffDashboard::class', $source);
    }
}
