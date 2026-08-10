<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use App\Filament\Admin\Pages\Dashboards\TransactionDashboard;
use App\Filament\Admin\Widgets\TransactionOverview;
use Tests\TestCase;

class TransactionDashboardTest extends TestCase
{
    public function test_transaction_dashboard_uses_shared_date_filtering_and_overview_widget(): void
    {
        self::assertTrue(is_subclass_of(TransactionDashboard::class, TimeFilteredDashboard::class));

        $dashboard = new TransactionDashboard();

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
}
