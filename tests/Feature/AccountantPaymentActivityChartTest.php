<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\AccountantDashboard;
use App\Filament\Admin\Widgets\AccountantPaymentActivityChart;
use App\Filament\Admin\Widgets\AccountantReceivablesStats;
use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class AccountantPaymentActivityChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_dashboard_uses_payment_activity_instead_of_restaurant_order_revenue(): void
    {
        $widgets = (new AccountantDashboard)->getWidgets();

        self::assertContains(AccountantPaymentActivityChart::class, $widgets);
        self::assertNotContains(RestaurantRevenueChart::class, $widgets);
        self::assertLessThan(
            array_search(AccountantPaymentActivityChart::class, $widgets, true),
            array_search(AccountantReceivablesStats::class, $widgets, true),
        );
    }

    public function test_chart_uses_the_same_collected_and_refunded_payment_semantics_as_accountant_metrics(): void
    {
        $this->payment('paid', 100, 'PAID-001', '2026-08-03 09:00:00');
        $this->payment('completed', 50, 'COMPLETED-001', '2026-08-04 10:00:00');
        $this->payment('refunded', 25, 'REFUNDED-001', '2026-08-04 11:00:00');
        $this->payment('refund', 5, 'REFUND-001', '2026-08-05 12:00:00');
        $this->payment('pending', 999, 'PENDING-001', '2026-08-04 13:00:00');
        $this->payment('paid', 900, 'OUTSIDE-RANGE-001', '2026-08-10 09:00:00');

        $data = $this->chartData('weekly', '2026-08-03', '2026-08-09');

        self::assertSame([100.0, 50.0, 0.0, 0.0, 0.0, 0.0, 0.0], $data['datasets'][0]['data']);
        self::assertSame([0.0, 25.0, 5.0, 0.0, 0.0, 0.0, 0.0], $data['datasets'][1]['data']);
        self::assertSame([1, 1, 0, 0, 0, 0, 0], $data['datasets'][2]['data']);
    }

    #[DataProvider('breakdownBuckets')]
    public function test_selected_breakdown_controls_payment_chart_granularity(
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

    public function test_accountant_chart_and_receivable_metrics_use_distinct_semantic_colors(): void
    {
        $data = $this->chartData('weekly', '2026-08-03', '2026-08-09');

        self::assertSame(['Collected Revenue (GHS)', 'Refunded (GHS)', 'Payments Received'], array_column($data['datasets'], 'label'));
        self::assertSame(['#059669', '#E11D48', '#4F46E5'], array_column($data['datasets'], 'borderColor'));
        self::assertCount(3, array_unique(array_column($data['datasets'], 'borderColor')));

        $stats = $this->stats(new AccountantReceivablesStats);

        self::assertSame('primary', $stats['Hotel booking receivables']->getColor());
        self::assertSame('info', $stats['Conference receivables']->getColor());
        self::assertSame('warning', $stats['Table-reservation receivables']->getColor());
        self::assertSame('success', $stats['Food-order receivables']->getColor());
        self::assertSame('danger', $stats['Corporate-billed receivables']->getColor());
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

    /**
     * @return array<string, mixed>
     */
    private function chartData(string $period, string $startDate, string $endDate): array
    {
        $widget = new AccountantPaymentActivityChart;
        $widget->pageFilters = [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $method = new ReflectionMethod($widget, 'getData');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(object $widget): array
    {
        $widget->pageFilters = [
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-09',
        ];

        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    private function payment(string $status, float $amount, string $reference, string $createdAt): void
    {
        Payment::query()->forceCreate([
            'amount' => $amount,
            'method' => 'cash',
            'payment_status' => $status,
            'transaction_reference' => $reference,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
