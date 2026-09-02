<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use App\Models\RestaurantOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class SuperAdminDashboardChartBreakdownTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('breakdownBuckets')]
    public function test_selected_breakdown_controls_revenue_chart_granularity(
        string $period,
        string $startDate,
        string $endDate,
        int $expectedBuckets,
        string $firstLabel,
        string $lastLabel,
    ): void {
        $data = $this->chartData($period, $startDate, $endDate);

        self::assertCount($expectedBuckets, $data['labels']);
        self::assertSame($firstLabel, $data['labels'][0]);
        self::assertSame($lastLabel, $data['labels'][$expectedBuckets - 1]);
        self::assertCount($expectedBuckets, $data['datasets'][0]['data']);
        self::assertCount($expectedBuckets, $data['datasets'][1]['data']);
    }

    public function test_weekly_breakdown_aggregates_revenue_and_order_counts_into_daily_buckets(): void
    {
        $this->createOrder('WEEKLY-1', 'completed', 100, '2026-08-03 09:00:00');
        $this->createOrder('WEEKLY-2', 'completed', 50, '2026-08-04 10:00:00');
        $this->createOrder('WEEKLY-3', 'pending', 70, '2026-08-04 11:00:00');
        $this->createOrder('OUTSIDE-WEEK', 'completed', 999, '2026-08-10 09:00:00');

        $data = $this->chartData('weekly', '2026-08-03', '2026-08-09');

        self::assertSame([100.0, 50.0, 0.0, 0.0, 0.0, 0.0, 0.0], $data['datasets'][0]['data']);
        self::assertSame([1, 2, 0, 0, 0, 0, 0], $data['datasets'][1]['data']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: int, 4: string, 5: string}>
     */
    public static function breakdownBuckets(): array
    {
        return [
            'daily uses hours' => ['daily', '2026-08-05', '2026-08-05', 24, '12 AM', '11 PM'],
            'weekly uses days' => ['weekly', '2026-08-03', '2026-08-09', 7, 'Mon Aug 3', 'Sun Aug 9'],
            'monthly uses days' => ['monthly', '2026-08-01', '2026-08-31', 31, 'Aug 1', 'Aug 31'],
            'quarterly uses months' => ['quarterly', '2026-07-01', '2026-09-30', 3, 'Jul 2026', 'Sep 2026'],
            'yearly uses months' => ['yearly', '2026-01-01', '2026-12-31', 12, 'Jan 2026', 'Dec 2026'],
        ];
    }

    private function chartData(string $period, string $startDate, string $endDate): array
    {
        $widget = new RestaurantRevenueChart;
        $widget->pageFilters = [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $method = new ReflectionMethod($widget, 'getData');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }

    private function createOrder(string $number, string $paymentStatus, float $total, string $createdAt): RestaurantOrder
    {
        $order = RestaurantOrder::query()->create([
            'order_number' => $number,
            'payment_status' => $paymentStatus,
            'status' => 'confirmed',
            'total' => $total,
            'ordering_channel' => 'web',
        ]);

        $order->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();

        return $order;
    }
}
