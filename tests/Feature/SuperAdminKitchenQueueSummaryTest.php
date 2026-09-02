<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\SuperAdminDashboard;
use App\Filament\Admin\Widgets\ExecutiveKitchenQueueSummary;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Models\RestaurantOrder;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class SuperAdminKitchenQueueSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_super_admin_dashboard_replaces_the_actionable_queue_with_a_read_only_summary(): void
    {
        $dashboard = new SuperAdminDashboard;
        $widgets = $dashboard->getWidgets();
        $summary = 'App\\Filament\\Admin\\Widgets\\ExecutiveKitchenQueueSummary';

        self::assertContains($summary, $widgets);
        self::assertNotContains(KitchenOrderQueue::class, $widgets);
        self::assertTrue(is_subclass_of($summary, StatsOverviewWidget::class));

        $polling = new ReflectionMethod(new ExecutiveKitchenQueueSummary, 'getPollingInterval');
        $polling->setAccessible(true);
        self::assertNull($polling->invoke(new ExecutiveKitchenQueueSummary));

        $layout = new ReflectionMethod($dashboard, 'dashboardWidgetLayout');
        $layout->setAccessible(true);
        [, $sections] = $layout->invoke($dashboard);

        self::assertContains($summary, $sections['Kitchen']);
    }

    public function test_summary_counts_eligible_queue_orders_in_the_selected_period(): void
    {
        $this->order('confirmed', 'completed', null, 'QUEUE-WAITING', '2026-08-05 09:00:00');
        $this->order('preparing', 'pending', 'corporate_account', 'QUEUE-PREPARING', '2026-08-06 09:00:00');
        $this->order('ready', 'completed', null, 'QUEUE-READY', '2026-08-07 09:00:00');
        $this->order('confirmed', 'pending', null, 'UNPAID', '2026-08-08 09:00:00');
        $this->order('served', 'completed', null, 'SERVED', '2026-08-09 09:00:00');
        $this->order('confirmed', 'completed', null, 'OUTSIDE-PERIOD', '2026-07-31 09:00:00');

        $stats = $this->stats();

        self::assertSame([
            'Total active queue',
            'Waiting to start',
            'Preparing',
            'Ready to serve',
        ], array_keys($stats));
        self::assertSame('3', $stats['Total active queue']->getValue());
        self::assertSame('1', $stats['Waiting to start']->getValue());
        self::assertSame('1', $stats['Preparing']->getValue());
        self::assertSame('1', $stats['Ready to serve']->getValue());
    }

    public function test_summary_cards_link_to_the_matching_date_filtered_food_orders(): void
    {
        $stats = $this->stats();

        $this->assertFoodOrderLink($stats['Total active queue'], 'kitchen_queue');
        $this->assertFoodOrderLink($stats['Waiting to start'], 'confirmed');
        $this->assertFoodOrderLink($stats['Preparing'], 'preparing');
        $this->assertFoodOrderLink($stats['Ready to serve'], 'ready');
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(): array
    {
        $widget = new ExecutiveKitchenQueueSummary;
        $widget->pageFilters = [
            'period' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ];
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    private function assertFoodOrderLink(Stat $stat, string $status): void
    {
        parse_str((string) parse_url((string) $stat->getUrl(), PHP_URL_QUERY), $query);

        self::assertSame('/admin/restaurant-orders', parse_url((string) $stat->getUrl(), PHP_URL_PATH));
        self::assertSame('2026-08-01', $query['filters']['created_at']['created_from']);
        self::assertSame('2026-08-15', $query['filters']['created_at']['created_until']);
        self::assertSame($status, $query['filters']['status']['value']);
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stat->getDescriptionIcon());
    }

    private function order(
        string $status,
        string $paymentStatus,
        ?string $paymentMethod,
        string $number,
        string $createdAt,
    ): RestaurantOrder {
        $order = RestaurantOrder::query()->create([
            'order_number' => $number,
            'subtotal' => 100,
            'total' => 100,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
        ]);
        $order->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();

        return $order;
    }
}
