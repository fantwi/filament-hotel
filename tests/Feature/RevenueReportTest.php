<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RevenueReport;
use App\Filament\Admin\Widgets\RevenueReportStats;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_report_uses_one_selected_period_for_structured_financial_data(): void
    {
        $reportPage = new RevenueReport;
        $reportPage->period = 'this_quarter';

        self::assertSame('This quarter', $reportPage->periodLabel());

        $report = $reportPage->report();

        self::assertArrayHasKey('netRevenue', $report);
        self::assertArrayHasKey('outstandingBreakdown', $report);
        self::assertArrayHasKey('paymentsReceived', $report);
        self::assertArrayHasKey('food', $report['outstandingBreakdown']);
    }

    public function test_revenue_stats_widget_uses_period_aware_overview_stats(): void
    {
        self::assertTrue(is_subclass_of(RevenueReportStats::class, StatsOverviewWidget::class));

        $widget = new RevenueReportStats;
        $widget->period = 'this_quarter';
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertCount(4, $stats);
    }
}
