<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use App\Filament\Admin\Widgets\RestaurantOrderReportStats;
use App\Models\RestaurantOrder;
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

    public function test_cancelled_orders_do_not_inflate_payment_metrics(): void
    {
        $this->travelTo('2026-09-18 14:30:00');

        foreach ([
            ['order_number' => 'FOOD-PAID', 'status' => 'served', 'payment_status' => 'completed', 'total' => 100],
            ['order_number' => 'FOOD-PENDING', 'status' => 'pending', 'payment_status' => 'pending', 'total' => 80],
            ['order_number' => 'FOOD-CANCELLED-PAID', 'status' => 'cancelled', 'payment_status' => 'completed', 'total' => 200],
            ['order_number' => 'FOOD-CANCELLED-PENDING', 'status' => 'cancelled', 'payment_status' => 'pending', 'total' => 90],
            ['order_number' => 'FOOD-FAILED', 'status' => 'pending', 'payment_status' => 'failed', 'total' => 70],
            ['order_number' => 'FOOD-REFUNDED', 'status' => 'served', 'payment_status' => 'refunded', 'total' => 50],
        ] as $order) {
            RestaurantOrder::query()->create([
                ...$order,
                'subtotal' => $order['total'],
            ]);
        }

        $metrics = (new RestaurantOrderReport)->getReportMetrics();

        self::assertSame(6, $metrics['totalOrders']);
        self::assertSame(1, $metrics['paidOrders']);
        self::assertSame(1, $metrics['pendingOrders']);
        self::assertSame(2, $metrics['cancelledOrders']);
        self::assertSame(100.0, $metrics['revenue']);
        self::assertSame(80.0, $metrics['outstanding']);
        self::assertSame(100.0, $metrics['averageOrderValue']);
        self::assertSame(25.0, $metrics['paymentRate']);
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
