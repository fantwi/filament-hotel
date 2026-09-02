<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\AdminDashboard;
use App\Filament\Admin\Widgets\AdminFinanceStats;
use App\Filament\Admin\Widgets\AdminServiceStats;
use App\Filament\Admin\Widgets\CorporateBillingOverview;
use App\Filament\Admin\Widgets\ExecutiveKitchenQueueSummary;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\ManagerOperationsChart;
use App\Filament\Admin\Widgets\RecentPayments;
use App\Filament\Admin\Widgets\RoleDashboardOverview;
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

class AdminDashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_authenticated_admin_sees_the_dashboard_hierarchy_in_reading_order(): void
    {
        $this->actingAsAdmin();

        $this->get('/admin/admin-dashboard')
            ->assertOk()
            ->assertSeeInOrder([
                'Dashboard period',
                RoleDashboardOverview::class,
                AdminServiceStats::class,
                AdminFinanceStats::class,
                'Operations',
                'Finance',
                'Kitchen',
            ], escape: false)
            ->assertSee(ExecutiveKitchenQueueSummary::class, escape: false)
            ->assertDontSee('Live Kitchen Order Queue');
    }

    public function test_dashboard_shell_preserves_responsive_columns_and_persistent_overflow_tabs(): void
    {
        $this->actingAsAdmin();
        $dashboard = new AdminDashboard;
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
            'Kitchen',
        ], array_map(
            fn ($tab): string => $tab->getLabel(),
            $components[2]->getDefaultChildComponents(),
        ));
    }

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
                ExecutiveKitchenQueueSummary::class,
            ],
        ], $sections);

        $allWidgets = [...$priorityWidgets, ...array_merge(...array_values($sections))];

        self::assertCount(count(array_unique($allWidgets)), $allWidgets);
    }

    public function test_widgets_stack_full_width_at_mobile_breakpoints(): void
    {
        foreach ((new AdminDashboard)->getWidgets() as $widgetClass) {
            self::assertSame('full', (new $widgetClass)->getColumnSpan(), $widgetClass);
        }
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

    private function actingAsAdmin(): User
    {
        foreach ([
            'view admin dashboard',
            'view kitchen dashboard',
            'view kitchen stock',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['department' => 'admin']);
        $user->assignRole($role);
        $user->givePermissionTo([
            'view admin dashboard',
            'view kitchen dashboard',
            'view kitchen stock',
        ]);

        $this->actingAs($user);

        return $user;
    }
}
