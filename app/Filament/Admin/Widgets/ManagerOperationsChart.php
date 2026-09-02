<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

/**
 * Provides the manager operations chart Filament dashboard widget.
 */
class ManagerOperationsChart extends ChartWidget
{
    use InteractsWithDashboardDateRange;

    protected ?string $heading = 'Operations by Selected Date Range';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    /**
     * Builds and returns data.
     */
    protected function getData(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $period = $this->selectedBreakdown();
        $granularity = $this->granularityFor($period);
        $buckets = $this->chartBuckets($start, $end, $period, $granularity);
        $totals = collect([
            Booking::class,
            ConferenceBooking::class,
            RestaurantReservation::class,
            RestaurantOrder::class,
        ])->mapWithKeys(fn (string $model): array => [
            $model => $this->operationTotals($model, $start, $end, $granularity),
        ]);

        $series = fn (string $model): array => $buckets
            ->map(fn (array $bucket): int => (int) ($totals->get($model)[$bucket['key']] ?? 0))
            ->all();

        return ['datasets' => [
            ['label' => 'Hotel', 'data' => $series(Booking::class)],
            ['label' => 'Conference', 'data' => $series(ConferenceBooking::class)],
            ['label' => 'Restaurant Reservations', 'data' => $series(RestaurantReservation::class)],
            ['label' => 'Food Orders', 'data' => $series(RestaurantOrder::class)],
        ], 'labels' => $buckets->pluck('label')->all()];
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
     * Builds every chart bucket, including periods with no activity.
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
     * Counts one operational record per selected chart bucket.
     *
     * @param  class-string  $model
     * @return array<string, int>
     */
    private function operationTotals(string $model, Carbon $start, Carbon $end, string $granularity): array
    {
        $expression = $this->bucketExpression($model, $granularity);

        return $model::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("{$expression} as bucket")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw($expression)
            ->pluck('total', 'bucket')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    /**
     * Returns a database-specific expression for each supported bucket size.
     *
     * @param  class-string  $model
     */
    private function bucketExpression(string $model, string $granularity): string
    {
        $driver = $model::query()->getModel()->getConnection()->getDriverName();

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
        return 'line';
    }
}
