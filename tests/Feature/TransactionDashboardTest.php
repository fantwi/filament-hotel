<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use App\Filament\Admin\Pages\Dashboards\TransactionDashboard;
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

    public function test_transaction_dashboard_registers_the_stats_before_the_detailed_overview(): void
    {
        $widgets = (new TransactionDashboard)->getWidgets();

        self::assertSame(TransactionStats::class, $widgets[0]);
        self::assertSame(TransactionOverview::class, $widgets[1]);
    }

    public function test_transaction_widgets_span_the_full_dashboard_width(): void
    {
        $stats = new TransactionStats;
        $overview = new TransactionOverview;

        self::assertSame('full', $stats->getColumnSpan());
        self::assertSame('full', $overview->getColumnSpan());
    }
}
