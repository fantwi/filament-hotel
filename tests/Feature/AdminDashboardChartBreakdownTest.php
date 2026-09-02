<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\ManagerOperationsChart;
use App\Models\RestaurantOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class AdminDashboardChartBreakdownTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('breakdownBuckets')]
    public function test_selected_breakdown_controls_operations_chart_granularity(
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

        foreach ($data['datasets'] as $dataset) {
            self::assertCount($expectedBuckets, $dataset['data']);
        }
    }

    public function test_weekly_breakdown_aggregates_each_operation_series_into_daily_buckets(): void
    {
        $this->createOrder('ADMIN-WEEKLY-1', '2026-08-03 09:00:00');
        $this->createOrder('ADMIN-WEEKLY-2', '2026-08-04 10:00:00');
        $this->createOrder('ADMIN-WEEKLY-3', '2026-08-04 11:00:00');
        $this->createOrder('ADMIN-OUTSIDE-WEEK', '2026-08-10 09:00:00');

        $data = $this->chartData('weekly', '2026-08-03', '2026-08-09');

        self::assertSame([0, 0, 0, 0, 0, 0, 0], $data['datasets'][0]['data']);
        self::assertSame([0, 0, 0, 0, 0, 0, 0], $data['datasets'][1]['data']);
        self::assertSame([0, 0, 0, 0, 0, 0, 0], $data['datasets'][2]['data']);
        self::assertSame([1, 2, 0, 0, 0, 0, 0], $data['datasets'][3]['data']);
    }

    public function test_operation_series_use_distinct_semantic_chart_colors(): void
    {
        $datasets = $this->chartData('monthly', '2026-08-01', '2026-08-31')['datasets'];

        self::assertSame([
            'Hotel' => '#4F46E5',
            'Conference' => '#0EA5E9',
            'Restaurant Reservations' => '#F59E0B',
            'Food Orders' => '#10B981',
        ], array_column($datasets, 'borderColor', 'label'));
        self::assertSame([
            'Hotel' => 'rgba(79, 70, 229, 0.18)',
            'Conference' => 'rgba(14, 165, 233, 0.18)',
            'Restaurant Reservations' => 'rgba(245, 158, 11, 0.18)',
            'Food Orders' => 'rgba(16, 185, 129, 0.18)',
        ], array_column($datasets, 'backgroundColor', 'label'));
        self::assertCount(4, array_unique(array_column($datasets, 'borderColor')));

        foreach ($datasets as $dataset) {
            self::assertSame($dataset['borderColor'], $dataset['pointBackgroundColor']);
            self::assertSame('#FFFFFF', $dataset['pointBorderColor']);
            self::assertSame(3, $dataset['pointRadius']);
            self::assertSame(3, $dataset['borderWidth']);
            self::assertSame(0.35, $dataset['tension']);
            self::assertTrue($dataset['fill']);
        }
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
        $widget = new ManagerOperationsChart;
        $widget->pageFilters = [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $method = new ReflectionMethod($widget, 'getData');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }

    private function createOrder(string $number, string $createdAt): RestaurantOrder
    {
        $order = RestaurantOrder::query()->create([
            'order_number' => $number,
            'payment_status' => 'completed',
            'status' => 'confirmed',
            'total' => 25,
            'ordering_channel' => 'web',
        ]);

        $order->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();

        return $order;
    }
}
