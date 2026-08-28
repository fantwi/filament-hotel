<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use App\Filament\Admin\Widgets\RestaurantOrderReportStats;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantOrderReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_report_provides_structured_summary_data_for_the_selected_period(): void
    {
        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'this_week';

        self::assertSame('This week', $reportPage->periodLabel());

        $report = $reportPage->getReportData();

        self::assertArrayHasKey('totalItems', $report);
        self::assertArrayHasKey('activeOrders', $report);
        self::assertArrayHasKey('paymentRate', $report);
        self::assertSame(0, $report['totalOrders']);
    }

    public function test_restaurant_report_stats_widget_uses_period_aware_overview_stats(): void
    {
        self::assertTrue(is_subclass_of(RestaurantOrderReportStats::class, StatsOverviewWidget::class));

        $widget = new RestaurantOrderReportStats;
        $widget->period = 'this_week';
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertCount(4, $stats);
    }
}
