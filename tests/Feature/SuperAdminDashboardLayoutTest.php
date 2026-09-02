<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\SuperAdminDashboard;
use App\Filament\Admin\Widgets\BestSellingMenuItems;
use App\Filament\Admin\Widgets\CorporateBillingOverview;
use App\Filament\Admin\Widgets\ExecutiveKitchenQueueSummary;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\RestaurantOrderStatusChart;
use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use App\Filament\Admin\Widgets\RoleDashboardOverview;
use App\Filament\Admin\Widgets\SuperAdminFinanceStats;
use App\Filament\Admin\Widgets\SuperAdminOperationsStats;
use App\Filament\Admin\Widgets\SuperAdminStats;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminDashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_authenticated_super_admin_sees_the_dashboard_hierarchy_in_reading_order(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/admin/super-admin-dashboard')
            ->assertOk()
            ->assertSeeInOrder([
                'Dashboard period',
                RoleDashboardOverview::class,
                SuperAdminStats::class,
                'Operations',
                'Finance',
                'Restaurant',
                'Kitchen',
            ], escape: false)
            ->assertSee(ExecutiveKitchenQueueSummary::class, escape: false)
            ->assertDontSee('Live Kitchen Order Queue');
    }

    public function test_dashboard_shell_preserves_responsive_columns_and_persistent_overflow_tabs(): void
    {
        $this->actingAsSuperAdmin();
        $dashboard = new SuperAdminDashboard;
        $components = $dashboard->content(Schema::make())->getComponents();

        self::assertSame(['default' => 1, 'md' => 2, 'xl' => 3], $dashboard->getColumns());
        self::assertInstanceOf(Grid::class, $components[1]);
        self::assertInstanceOf(Tabs::class, $components[2]);
        self::assertFalse($components[2]->isScrollable());
        self::assertTrue($components[2]->isTabPersistedInQueryString());
        self::assertSame('dashboard-section', $components[2]->getTabQueryStringKey());
        self::assertSame([
            'Operations',
            'Finance',
            'Restaurant',
            'Kitchen',
        ], array_map(
            fn ($tab): string => $tab->getLabel(),
            $components[2]->getDefaultChildComponents(),
        ));
    }

    public function test_every_widget_appears_once_in_the_intended_dashboard_region(): void
    {
        [$priorityWidgets, $sections] = $this->dashboardWidgetLayout();

        self::assertSame([
            RoleDashboardOverview::class,
            SuperAdminStats::class,
        ], $priorityWidgets);
        self::assertSame([
            'Operations' => [
                SuperAdminOperationsStats::class,
            ],
            'Finance' => [
                SuperAdminFinanceStats::class,
                CorporateBillingOverview::class,
            ],
            'Restaurant' => [
                RestaurantRevenueChart::class,
                RestaurantOrderStatusChart::class,
                BestSellingMenuItems::class,
            ],
            'Kitchen' => [
                KitchenStockStats::class,
                ExecutiveKitchenQueueSummary::class,
            ],
        ], $sections);

        $allWidgets = [...$priorityWidgets, ...array_merge(...array_values($sections))];

        self::assertCount(count(array_unique($allWidgets)), $allWidgets);
        self::assertNotContains(KitchenOrderQueue::class, $allWidgets);
    }

    public function test_widget_spans_stack_on_mobile_and_pair_restaurant_charts_on_desktop(): void
    {
        foreach ([
            RoleDashboardOverview::class,
            SuperAdminStats::class,
            SuperAdminOperationsStats::class,
            SuperAdminFinanceStats::class,
            CorporateBillingOverview::class,
            BestSellingMenuItems::class,
            KitchenStockStats::class,
            ExecutiveKitchenQueueSummary::class,
        ] as $widgetClass) {
            self::assertSame('full', (new $widgetClass)->getColumnSpan(), $widgetClass);
        }

        self::assertSame([
            'default' => 'full',
            'lg' => 2,
        ], (new RestaurantRevenueChart)->getColumnSpan());
        self::assertSame([
            'default' => 'full',
            'lg' => 1,
        ], (new RestaurantOrderStatusChart)->getColumnSpan());
    }

    /**
     * @return array{0: array<int, class-string>, 1: array<string, array<int, class-string>>}
     */
    private function dashboardWidgetLayout(): array
    {
        $dashboard = new SuperAdminDashboard;
        $method = new ReflectionMethod($dashboard, 'dashboardWidgetLayout');
        $method->setAccessible(true);

        return $method->invoke($dashboard);
    }

    private function actingAsSuperAdmin(): User
    {
        foreach ([
            'view super admin dashboard',
            'view kitchen dashboard',
            'view kitchen stock',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create(['department' => 'super_admin']);
        $user->assignRole($role);
        $user->givePermissionTo([
            'view super admin dashboard',
            'view kitchen dashboard',
            'view kitchen stock',
        ]);

        $this->actingAs($user);

        return $user;
    }
}
