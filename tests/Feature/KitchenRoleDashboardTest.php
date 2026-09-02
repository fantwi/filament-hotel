<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\KitchenManagerDashboard;
use App\Filament\Admin\Pages\Dashboards\KitchenStaffDashboard;
use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use App\Filament\Admin\Widgets\KitchenManagerStats;
use App\Filament\Admin\Widgets\KitchenStaffStats;
use App\Models\User;
use App\Services\StaffAccountAccess;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenRoleDashboardTest extends TestCase
{
    use RefreshDatabase;

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
        $kitchenManager = User::factory()->create(['department' => 'kitchen_manager']);
        $kitchenStaff = User::factory()->create(['department' => 'kitchen_staff']);
        $access = app(StaffAccountAccess::class);

        self::assertSame(
            'filament.admin.pages.kitchen-manager-dashboard',
            $access->dashboardRouteName($kitchenManager),
        );
        self::assertSame(
            'filament.admin.pages.kitchen-staff-dashboard',
            $access->dashboardRouteName($kitchenStaff),
        );
    }
}
