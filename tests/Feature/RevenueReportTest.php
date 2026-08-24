<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RevenueReport;
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
}
