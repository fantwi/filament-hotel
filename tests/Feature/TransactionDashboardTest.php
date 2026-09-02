<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\AccountantDashboard;
use App\Filament\Admin\Pages\Dashboards\AdminDashboard;
use App\Filament\Admin\Pages\Dashboards\ManagerDashboard;
use App\Filament\Admin\Pages\Dashboards\ReceptionDashboard;
use App\Filament\Admin\Pages\Dashboards\SuperAdminDashboard;
use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use App\Filament\Admin\Pages\Dashboards\TransactionDashboard;
use App\Filament\Admin\Widgets\RoleDashboardOverview;
use App\Filament\Admin\Widgets\TransactionOverview;
use App\Filament\Admin\Widgets\TransactionStats;
use Filament\Widgets\StatsOverviewWidget;
use Tests\TestCase;

class TransactionDashboardTest extends TestCase
{
    public function test_transaction_dashboard_uses_shared_date_filtering_and_overview_widget(): void
    {
        self::assertTrue(is_subclass_of(TransactionDashboard::class, TimeFilteredDashboard::class));

        $dashboard = new TransactionDashboard;

        self::assertSame([TransactionStats::class, TransactionOverview::class], $dashboard->getWidgets());
        self::assertTrue(is_subclass_of(TransactionStats::class, StatsOverviewWidget::class));
        self::assertContains(InteractsWithDashboardDateRange::class, class_uses_recursive(TransactionStats::class));
        self::assertSame(1, $dashboard->getColumns());
    }

    public function test_transaction_overview_includes_the_responsive_breakdown_and_corporate_follow_up(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/widgets/transaction-overview.blade.php'));

        self::assertStringContainsString('Outstanding follow-up', $view);
        self::assertStringContainsString('Corporate credit awaiting payment', $view);
        self::assertStringContainsString('grid gap-4 sm:grid-cols-2 xl:grid-cols-4', $view);
        self::assertStringContainsString('Report notes', $view);
    }

    public function test_transaction_breakdown_uses_metric_cards_at_all_breakpoints(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/widgets/transaction-overview.blade.php'));

        self::assertStringContainsString('sm:grid-cols-2 xl:grid-cols-4', $view);
        self::assertStringContainsString('Payments received', $view);
        self::assertStringContainsString('Corporate outstanding', $view);
        self::assertStringNotContainsString('hidden overflow-x-auto md:block', $view);
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
            self::assertSame(RoleDashboardOverview::class, (new $dashboardClass)->getWidgets()[0]);
        }

        $view = file_get_contents(resource_path('views/filament/admin/widgets/role-dashboard-overview.blade.php'));

        self::assertStringContainsString('md:grid-cols-3', $view);
    }
}
