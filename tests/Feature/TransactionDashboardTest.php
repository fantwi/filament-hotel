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
}
