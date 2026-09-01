<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use App\Filament\Admin\Widgets\RestaurantOrderReportStats;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class RestaurantOrderReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_report_provides_structured_summary_data_for_the_selected_period(): void
    {
        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'weekly';

        self::assertSame('Weekly', $reportPage->periodLabel());

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
        $widget->period = 'weekly';
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertCount(4, $stats);
    }

    public function test_restaurant_order_register_uses_server_side_pagination(): void
    {
        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'monthly';
        $reportPage->perPage = 10;

        $report = $reportPage->getReportData();

        self::assertInstanceOf(LengthAwarePaginator::class, $report['orders']);
        self::assertSame(0, $report['orders']->total());
    }

    public function test_restaurant_order_report_has_a_mobile_card_register_and_pagination_controls(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/pages/restaurant-order-report.blade.php'));

        self::assertStringContainsString('md:hidden', $view);
        self::assertStringContainsString('Order cards', $view);
        self::assertStringContainsString('hasPages()', $view);
        self::assertStringContainsString('$report[\'orders\']->links()', $view);
        self::assertStringContainsString('Rows per page', $view);
    }
}
