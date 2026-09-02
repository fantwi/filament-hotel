<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\RestaurantOrder;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

/**
 * Provides the restaurant revenue chart Filament dashboard widget.
 */
class RestaurantRevenueChart extends ChartWidget
{
    use InteractsWithDashboardDateRange;

    protected ?string $heading = 'Restaurant Revenue by Selected Date Range';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

    /**
     * Builds and returns data.
     */
    protected function getData(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $period = $this->selectedBreakdown();
        $granularity = $this->granularityFor($period);
        $buckets = $this->chartBuckets($start, $end, $period, $granularity);
        $aggregates = $this->orderAggregates($start, $end, $granularity);
        $labels = $buckets->pluck('label')->all();
        $revenueData = $buckets
            ->map(fn (array $bucket): float => (float) ($aggregates->get($bucket['key'])?->revenue ?? 0))
            ->all();
        $orderCountData = $buckets
            ->map(fn (array $bucket): int => (int) ($aggregates->get($bucket['key'])?->order_count ?? 0))
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (GHS)',
                    'data' => $revenueData,
                    'yAxisID' => 'y',
                    'borderColor' => '#D97706',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.72)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Orders',
                    'data' => $orderCountData,
                    'type' => 'line',
                    'yAxisID' => 'y1',
                    'borderColor' => '#0EA5E9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.18)',
                    'pointBackgroundColor' => '#0284C7',
                    'pointBorderColor' => '#FFFFFF',
                    'pointRadius' => 3,
                    'borderWidth' => 3,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
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
     * Builds every chart bucket, including periods with no orders.
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
     * Aggregates revenue and order volume in one database query.
     *
     * @return Collection<string, object>
     */
    private function orderAggregates(Carbon $start, Carbon $end, string $granularity): Collection
    {
        $expression = $this->bucketExpression($granularity);

        return RestaurantOrder::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("{$expression} as bucket")
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(CASE WHEN payment_status = ? THEN total ELSE 0 END) as revenue', ['completed'])
            ->groupByRaw($expression)
            ->get()
            ->keyBy(fn (object $aggregate): string => (string) $aggregate->bucket);
    }

    /**
     * Returns a database-specific expression for each supported bucket size.
     */
    private function bucketExpression(string $granularity): string
    {
        $driver = RestaurantOrder::query()->getModel()->getConnection()->getDriverName();

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

    /**
     * Builds and returns type.
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Builds and returns options.
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'scales' => [
                'y' => ['beginAtZero' => true, 'position' => 'left'],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                ],
            ],
        ];
    }

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
