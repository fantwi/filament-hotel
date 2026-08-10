<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\AccountantDashboard;
use App\Filament\Admin\Pages\Dashboards\AdminDashboard;
use App\Filament\Admin\Pages\Dashboards\ManagerDashboard;
use App\Filament\Admin\Pages\Dashboards\ReceptionDashboard;
use App\Filament\Admin\Pages\Dashboards\RoleDashboard;
use App\Filament\Admin\Pages\Dashboards\SuperAdminDashboard;
use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use App\Filament\Admin\Widgets\RoleDashboardOverview;
use App\Filament\Admin\Widgets\TransactionOverview;
use Tests\TestCase;

class TransactionDashboardTest extends TestCase
{
    public function test_transaction_dashboard_uses_shared_date_filtering_and_overview_widget(): void
    {
        self::assertTrue(is_subclass_of(\App\Filament\Admin\Pages\Dashboards\TransactionDashboard::class, TimeFilteredDashboard::class));

        $dashboard = new \App\Filament\Admin\Pages\Dashboards\TransactionDashboard();

        self::assertSame([TransactionOverview::class], $dashboard->getWidgets());
        self::assertSame(1, $dashboard->getColumns());
    }

    public function test_transaction_overview_includes_the_responsive_breakdown_and_corporate_follow_up(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/widgets/transaction-overview.blade.php'));

        self::assertStringContainsString('Outstanding follow-up', $view);
        self::assertStringContainsString('Corporate credit awaiting payment', $view);
        self::assertStringContainsString('md:hidden', $view);
        self::assertStringContainsString('Report notes', $view);
    }

    public function test_role_dashboards_begin_with_the_responsive_role_overview(): void
    {
        foreach ([
            ReceptionDashboard::class,
            ManagerDashboard::class,
            AccountantDashboard::class,
            AdminDashboard::class,
            SuperAdminDashboard::class,
        ] as $dashboardClass) {
            self::assertTrue(is_subclass_of($dashboardClass, TimeFilteredDashboard::class));
            self::assertSame(RoleDashboardOverview::class, (new $dashboardClass())->getWidgets()[0]);
        }

        $view = file_get_contents(resource_path('views/filament/admin/widgets/role-dashboard-overview.blade.php'));

        self::assertStringContainsString('Reporting period', $view);
        self::assertStringContainsString('md:grid-cols-3', $view);
    }
}
