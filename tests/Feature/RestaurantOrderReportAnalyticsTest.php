<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use App\Filament\Admin\Widgets\RestaurantOrderComparisonStats;
use App\Filament\Admin\Widgets\RestaurantOrderRevenueTrendChart;
use App\Filament\Admin\Widgets\RestaurantOrderVolumeTrendChart;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RestaurantOrderReportAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_compares_the_previous_equal_length_period_and_fills_trend_gaps(): void
    {
        $legacyOrder = $this->order('FOOD-LEGACY', '2026-07-01 09:00:00');
        $previousRefund = $this->payment($legacyOrder, 20, '2026-07-02 09:00:00', 'PREVIOUS-REFUND');
        $currentRefund = $this->payment($legacyOrder, 50, '2026-07-03 09:00:00', 'CURRENT-REFUND');

        $previousFirst = $this->order('FOOD-PREVIOUS-FIRST', '2026-08-07 09:00:00', 2);
        $this->order('FOOD-PREVIOUS-SECOND', '2026-08-09 09:00:00', 1);
        $this->payment($previousFirst, 100, '2026-08-08 10:00:00', 'PREVIOUS-COLLECTION');

        $this->travelTo('2026-08-09 12:00:00');
        $previousRefund->update(['payment_status' => 'refunded']);

        $currentFirst = $this->order('FOOD-CURRENT-FIRST', '2026-08-10 09:00:00', 4);
        $this->order('FOOD-CURRENT-SECOND', '2026-08-12 09:00:00', 2);
        $this->payment($currentFirst, 200, '2026-08-10 11:00:00', 'CURRENT-COLLECTION');

        $this->travelTo('2026-08-12 12:00:00');
        $currentRefund->update(['payment_status' => 'refunded']);

        $report = $this->reportForPeriod('2026-08-10', '2026-08-12');

        self::assertSame('Aug 7, 2026 to Aug 9, 2026', $report['comparison']['previousPeriodLabel']);
        self::assertSame([
            'current' => 2,
            'previous' => 2,
            'difference' => 0,
            'percentageChange' => 0.0,
        ], $report['comparison']['orders']);
        self::assertSame([
            'current' => 6,
            'previous' => 3,
            'difference' => 3,
            'percentageChange' => 100.0,
        ], $report['comparison']['items']);
        self::assertSame([
            'current' => 150.0,
            'previous' => 80.0,
            'difference' => 70.0,
            'percentageChange' => 87.5,
        ], $report['comparison']['netRevenue']);
        self::assertSame([
            'granularity' => 'day',
            'labels' => ['Aug 10', 'Aug 11', 'Aug 12'],
            'orders' => [1, 0, 1],
            'items' => [4, 0, 2],
            'collected' => [200.0, 0.0, 0.0],
            'refunds' => [0.0, 0.0, 50.0],
            'netRevenue' => [200.0, 0.0, -50.0],
        ], $report['trend']);
    }

    public function test_trend_uses_readable_automatic_granularity_for_short_and_long_ranges(): void
    {
        $hourly = $this->reportForPeriod('2026-08-10', '2026-08-10')['trend'];
        $monthly = $this->reportForPeriod('2026-08-01', '2026-09-15')['trend'];
        $yearly = $this->reportForPeriod('2024-01-01', '2026-01-01')['trend'];

        self::assertSame('hour', $hourly['granularity']);
        self::assertCount(24, $hourly['labels']);
        self::assertSame('month', $monthly['granularity']);
        self::assertSame(['Aug 2026', 'Sep 2026'], $monthly['labels']);
        self::assertSame('year', $yearly['granularity']);
        self::assertSame(['2024', '2025', '2026'], $yearly['labels']);
    }

    public function test_comparison_widget_uses_precomputed_values_without_queries(): void
    {
        self::assertTrue(is_subclass_of(RestaurantOrderComparisonStats::class, StatsOverviewWidget::class));

        $widget = new RestaurantOrderComparisonStats;
        $widget->comparison = [
            'previousPeriodLabel' => 'Aug 7, 2026 to Aug 9, 2026',
            'orders' => ['current' => 12, 'previous' => 8, 'difference' => 4, 'percentageChange' => 50.0],
            'items' => ['current' => 30, 'previous' => 20, 'difference' => 10, 'percentageChange' => 50.0],
            'netRevenue' => ['current' => 1250.0, 'previous' => 1000.0, 'difference' => 250.0, 'percentageChange' => 25.0],
        ];
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = $method->invoke($widget);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        self::assertCount(0, $queries);
        self::assertSame(
            ['Orders received change', 'Items ordered change', 'Net revenue change'],
            array_map(fn ($stat): string => $stat->getLabel(), $stats),
        );
        self::assertSame(
            ['+4', '+10', '+GHS 250.00'],
            array_map(fn ($stat): string => $stat->getValue(), $stats),
        );
        self::assertSame(['success', 'success', 'success'], array_map(
            fn ($stat): string|array|null => $stat->getColor(),
            $stats,
        ));
        self::assertStringContainsString('Selected period 12', $stats[0]->getDescription());
        self::assertStringContainsString('Previous period 8', $stats[0]->getDescription());
        self::assertStringContainsString('Up 50.0%', $stats[0]->getDescription());
        self::assertStringContainsString('Selected period GHS 1,250.00', $stats[2]->getDescription());
        self::assertStringContainsString('Aug 7, 2026 to Aug 9, 2026', $widget->getDescription());
    }

    public function test_trend_widgets_use_precomputed_values_without_queries(): void
    {
        self::assertTrue(is_subclass_of(RestaurantOrderVolumeTrendChart::class, ChartWidget::class));
        self::assertTrue(is_subclass_of(RestaurantOrderRevenueTrendChart::class, ChartWidget::class));

        $trend = [
            'granularity' => 'day',
            'labels' => ['Aug 10', 'Aug 11', 'Aug 12'],
            'orders' => [2, 0, 1],
            'items' => [6, 0, 3],
            'collected' => [200.0, 0.0, 100.0],
            'refunds' => [0.0, 30.0, 0.0],
            'netRevenue' => [200.0, -30.0, 100.0],
        ];
        $volumeWidget = new RestaurantOrderVolumeTrendChart;
        $volumeWidget->trend = $trend;
        $revenueWidget = new RestaurantOrderRevenueTrendChart;
        $revenueWidget->trend = $trend;
        $volumeDataMethod = new \ReflectionMethod($volumeWidget, 'getData');
        $volumeDataMethod->setAccessible(true);
        $revenueDataMethod = new \ReflectionMethod($revenueWidget, 'getData');
        $revenueDataMethod->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $volumeData = $volumeDataMethod->invoke($volumeWidget);
            $revenueData = $revenueDataMethod->invoke($revenueWidget);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        self::assertCount(0, $queries);
        self::assertSame($trend['labels'], $volumeData['labels']);
        self::assertSame(['Orders', 'Items ordered'], array_column($volumeData['datasets'], 'label'));
        self::assertSame(['#2563EB', '#7C3AED'], array_column($volumeData['datasets'], 'borderColor'));
        self::assertSame($trend['labels'], $revenueData['labels']);
        self::assertSame(
            ['Collected revenue', 'Refunds', 'Net revenue'],
            array_column($revenueData['datasets'], 'label'),
        );
        self::assertSame('line', $revenueData['datasets'][2]['type']);
        self::assertSame(['#059669', '#E11D48', '#4F46E5'], array_column($revenueData['datasets'], 'borderColor'));
    }

    public function test_report_renders_comparison_and_responsive_trends_before_operational_details(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $this->order('FOOD-ANALYTICS-LAYOUT', '2026-09-05 09:00:00', 2);
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(RestaurantOrderReport::getUrl([
            'period' => 'custom',
            'startDate' => '2026-09-01',
            'endDate' => '2026-09-30',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Restaurant performance comparison',
            'Order volume trend',
            'Restaurant revenue trend',
            'Payment status',
            'Live kitchen queue',
            'Order register',
        ]);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $results = $xpath->query('//*[@data-restaurant-report-results]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $results);
        self::assertSame(1, $xpath->query('.//section[@aria-label="Previous-period restaurant comparison"]', $results)?->count());
        self::assertSame(1, $xpath->query('.//section[@aria-label="Restaurant trend charts"]', $results)?->count());
        $trendSection = $xpath->query('.//section[@aria-label="Restaurant trend charts"]', $results)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $trendSection);
        self::assertStringContainsString('xl:grid-cols-2', $trendSection->getAttribute('class'));
    }

    /**
     * Returns a report for an explicit inclusive date range.
     */
    private function reportForPeriod(string $start, string $end): array
    {
        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = $start;
        $reportPage->endDate = $end;

        return $reportPage->getReportData();
    }

    /**
     * Creates one order and an optional item quantity at an exact timestamp.
     */
    private function order(string $number, string $createdAt, int $itemQuantity = 0): RestaurantOrder
    {
        $this->travelTo($createdAt);
        $order = RestaurantOrder::query()->create([
            'order_number' => $number,
            'ordering_channel' => 'web',
            'subtotal' => 100,
            'total' => 100,
            'status' => 'served',
            'payment_status' => 'completed',
        ]);

        if ($itemQuantity > 0) {
            $slug = strtolower($number);
            $category = MenuCategory::query()->create([
                'name' => 'Report category '.$number,
                'slug' => 'report-category-'.$slug,
            ]);
            $menuItem = MenuItem::query()->create([
                'menu_category_id' => $category->id,
                'name' => 'Report item '.$number,
                'slug' => 'report-item-'.$slug,
                'price' => 25,
            ]);
            RestaurantOrderItem::query()->create([
                'restaurant_order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'item_name' => $menuItem->name,
                'quantity' => $itemQuantity,
                'unit_price' => 25,
                'total_price' => 25 * $itemQuantity,
            ]);
        }

        return $order;
    }

    /**
     * Creates one restaurant-linked payment at an exact timestamp.
     */
    private function payment(
        RestaurantOrder $order,
        float $amount,
        string $createdAt,
        string $reference,
    ): Payment {
        $this->travelTo($createdAt);

        return Payment::query()->create([
            'restaurant_order_id' => $order->id,
            'amount' => $amount,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => $reference,
        ]);
    }
}
