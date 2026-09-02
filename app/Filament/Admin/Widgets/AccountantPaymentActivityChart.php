<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

/**
 * Charts collected payments and refunds using the Accountant KPI semantics.
 */
class AccountantPaymentActivityChart extends ChartWidget
{
    use InteractsWithDashboardDateRange;

    protected ?string $heading = 'Payment Activity by Selected Date Range';

    protected ?string $description = 'Collected revenue, refunds, and successful payment volume recorded during the selected period.';

    protected ?string $emptyStateHeading = 'No payment activity for this period';

    protected ?string $emptyStateDescription = 'Choose another dashboard period or date range to view payment activity.';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the selected period contains meaningful chart data.
     */
    public function isEmpty(): bool
    {
        return collect($this->getCachedData()['datasets'] ?? [])
            ->flatMap(fn (array $dataset): array => $dataset['data'] ?? [])
            ->every(fn (mixed $value): bool => (float) $value === 0.0);
    }

    /**
     * Builds payment totals and counts for each selected date bucket.
     */
    protected function getData(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $period = $this->selectedBreakdown();
        $granularity = $this->granularityFor($period);
        $buckets = $this->chartBuckets($start, $end, $period, $granularity);
        $aggregates = $this->paymentAggregates($start, $end, $granularity);

        return [
            'datasets' => [
                [
                    'label' => 'Collected Revenue (GHS)',
                    'data' => $buckets
                        ->map(fn (array $bucket): float => (float) ($aggregates->get($bucket['key'])?->collected_revenue ?? 0))
                        ->all(),
                    'yAxisID' => 'y',
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.72)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Refunded (GHS)',
                    'data' => $buckets
                        ->map(fn (array $bucket): float => (float) ($aggregates->get($bucket['key'])?->refunded_amount ?? 0))
                        ->all(),
                    'yAxisID' => 'y',
                    'borderColor' => '#E11D48',
                    'backgroundColor' => 'rgba(244, 63, 94, 0.62)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Payments Received',
                    'data' => $buckets
                        ->map(fn (array $bucket): int => (int) ($aggregates->get($bucket['key'])?->payment_count ?? 0))
                        ->all(),
                    'type' => 'line',
                    'yAxisID' => 'y1',
                    'borderColor' => '#4F46E5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.18)',
                    'pointBackgroundColor' => '#4338CA',
                    'pointBorderColor' => '#FFFFFF',
                    'pointRadius' => 3,
                    'borderWidth' => 3,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $buckets->pluck('label')->all(),
        ];
    }

    /**
     * Returns the validated dashboard breakdown selected by the user.
     */
    private function selectedBreakdown(): string
    {
        $period = (string) (($this->pageFilters ?? [])['period'] ?? 'monthly');

        return in_array($period, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'], true)
            ? $period
            : 'monthly';
    }

    /**
     * Maps dashboard periods to readable chart bucket sizes.
     */
    private function granularityFor(string $period): string
    {
        return match ($period) {
            'daily' => 'hour',
            'weekly', 'monthly' => 'day',
            default => 'month',
        };
    }

    /**
     * Builds every chart bucket, including periods without payment activity.
     *
     * @return Collection<int, array{key: string, label: string}>
     */
    private function chartBuckets(Carbon $start, Carbon $end, string $period, string $granularity): Collection
    {
        $cursor = match ($granularity) {
            'hour' => $start->copy()->startOfHour(),
            'day' => $start->copy()->startOfDay(),
            default => $start->copy()->startOfMonth(),
        };
        $buckets = collect();

        while ($cursor->lessThanOrEqualTo($end)) {
            $buckets->push([
                'key' => $this->bucketKey($cursor, $granularity),
                'label' => match ($period) {
                    'daily' => $cursor->format('g A'),
                    'weekly' => $cursor->format('D M j'),
                    'monthly' => $cursor->format('M j'),
                    default => $cursor->format('M Y'),
                },
            ]);

            match ($granularity) {
                'hour' => $cursor->addHour(),
                'day' => $cursor->addDay(),
                default => $cursor->addMonth(),
            };
        }

        return $buckets;
    }

    /**
     * Aggregates collected revenue, refunds, and payment count in one query.
     *
     * @return Collection<string, object>
     */
    private function paymentAggregates(Carbon $start, Carbon $end, string $granularity): Collection
    {
        $expression = $this->bucketExpression($granularity);

        return Payment::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("{$expression} as bucket")
            ->selectRaw("SUM(CASE WHEN payment_status IN ('paid', 'completed') THEN amount ELSE 0 END) as collected_revenue")
            ->selectRaw("SUM(CASE WHEN payment_status IN ('refunded', 'refund') THEN amount ELSE 0 END) as refunded_amount")
            ->selectRaw("SUM(CASE WHEN payment_status IN ('paid', 'completed') THEN 1 ELSE 0 END) as payment_count")
            ->groupByRaw($expression)
            ->get()
            ->keyBy(fn (object $aggregate): string => (string) $aggregate->bucket);
    }

    /**
     * Returns a database-specific expression for each supported bucket size.
     */
    private function bucketExpression(string $granularity): string
    {
        $driver = Payment::query()->getModel()->getConnection()->getDriverName();

        return match ($driver) {
            'sqlite' => match ($granularity) {
                'hour' => "strftime('%Y-%m-%d %H:00:00', created_at)",
                'day' => "strftime('%Y-%m-%d', created_at)",
                default => "strftime('%Y-%m-01', created_at)",
            },
            'pgsql' => match ($granularity) {
                'hour' => "to_char(date_trunc('hour', created_at), 'YYYY-MM-DD HH24:00:00')",
                'day' => "to_char(created_at, 'YYYY-MM-DD')",
                default => "to_char(created_at, 'YYYY-MM-01')",
            },
            default => match ($granularity) {
                'hour' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
                'day' => "DATE_FORMAT(created_at, '%Y-%m-%d')",
                default => "DATE_FORMAT(created_at, '%Y-%m-01')",
            },
        };
    }

    /**
     * Formats a bucket cursor to match its database aggregate key.
     */
    private function bucketKey(Carbon $cursor, string $granularity): string
    {
        return match ($granularity) {
            'hour' => $cursor->format('Y-m-d H:00:00'),
            'day' => $cursor->format('Y-m-d'),
            default => $cursor->format('Y-m-01'),
        };
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Keeps amount and transaction-count series readable at different scales.
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => ['display' => true, 'text' => 'Amount (GHS)'],
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                    'title' => ['display' => true, 'text' => 'Payments'],
                ],
            ],
        ];
    }

    /**
     * Limits the payment chart to staff allowed to view the Accountant dashboard.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view accountant dashboard') ?? false;
    }
}
