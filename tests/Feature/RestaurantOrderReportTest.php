<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use App\Filament\Admin\Widgets\RestaurantOrderReportStats;
use App\Models\Payment;
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
        self::assertSame('Net revenue', $stats[1]->getLabel());
        self::assertSame('GHS 0.00', $stats[1]->getValue());
        self::assertSame('GHS 0.00 collected · GHS 0.00 refunded', $stats[1]->getDescription());
        self::assertSame('Remaining balance on open orders', $stats[2]->getDescription());
        self::assertSame('Average collected order', $stats[3]->getLabel());
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

    public function test_cancelled_orders_do_not_inflate_order_payment_outcome_metrics(): void
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
        self::assertSame(25.0, $metrics['paymentRate']);
    }

    public function test_financial_metrics_follow_payment_and_refund_event_dates(): void
    {
        $this->travelTo('2026-07-10 09:00:00');
        $paidOrder = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-LATER-PAYMENT',
            'subtotal' => 300,
            'total' => 300,
            'status' => 'served',
            'payment_status' => 'completed',
        ]);
        $refundedOrder = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-LATER-REFUND',
            'subtotal' => 50,
            'total' => 50,
            'status' => 'served',
            'payment_status' => 'refunded',
        ]);

        $this->travelTo('2026-07-15 09:00:00');
        $refundedPayment = Payment::query()->create([
            'restaurant_order_id' => $refundedOrder->id,
            'amount' => 50,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-REFUND-EVENT',
        ]);

        $this->travelTo('2026-08-05 09:00:00');
        Payment::query()->create([
            'restaurant_order_id' => $paidOrder->id,
            'amount' => 120,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-LATER-PAYMENT-1',
        ]);

        $this->travelTo('2026-08-07 09:00:00');
        Payment::query()->create([
            'restaurant_order_id' => $paidOrder->id,
            'amount' => 80,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-LATER-PAYMENT-2',
        ]);

        $this->travelTo('2026-08-20 12:00:00');
        $refundedPayment->update(['payment_status' => 'refunded']);

        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';
        $metrics = $reportPage->getReportMetrics();

        self::assertSame(200.0, $metrics['revenue']);
        self::assertSame(50.0, $metrics['refunds']);
        self::assertSame(150.0, $metrics['netRevenue']);
        self::assertSame(1, $metrics['collectedOrderCount']);
        self::assertSame(200.0, $metrics['averageOrderValue']);
    }

    public function test_outstanding_uses_remaining_balances_after_collected_payments(): void
    {
        $this->travelTo('2026-08-10 09:00:00');
        $partiallyPaid = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-PARTIAL',
            'subtotal' => 400,
            'total' => 400,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
        $overpaid = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-OVERPAID',
            'subtotal' => 100,
            'total' => 100,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-CANCELLED-UNPAID',
            'subtotal' => 200,
            'total' => 200,
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ]);
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-SETTLED-NO-BALANCE',
            'subtotal' => 300,
            'total' => 300,
            'status' => 'served',
            'payment_status' => 'completed',
        ]);

        Payment::query()->create([
            'restaurant_order_id' => $partiallyPaid->id,
            'amount' => 150,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-PARTIAL-PAYMENT',
        ]);
        Payment::query()->create([
            'restaurant_order_id' => $overpaid->id,
            'amount' => 150,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-OVERPAYMENT',
        ]);

        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';

        self::assertSame(250.0, $reportPage->getReportMetrics()['outstanding']);
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
