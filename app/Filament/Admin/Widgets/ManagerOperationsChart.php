<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
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
        $granularity = $this->granularityFor($start, $end);
        $buckets = $this->chartBuckets($start, $end, $period, $granularity);
        $operations = [
            Booking::class => 'check_in',
            ConferenceBooking::class => 'booking_date',
            RestaurantReservation::class => 'reservation_date',
            RestaurantOrder::class => 'created_at',
        ];
        $totals = collect($operations)->mapWithKeys(fn (string $dateColumn, string $model): array => [
            $model => $this->operationTotals($model, $dateColumn, $start, $end, $granularity),
        ]);

        $series = fn (string $model): array => $buckets
            ->map(fn (array $bucket): int => (int) ($totals->get($model)[$bucket['key']] ?? 0))
            ->all();

        return ['datasets' => [
            $this->operationDataset(
                'Hotel',
                $series(Booking::class),
                '#4F46E5',
                'rgba(79, 70, 229, 0.18)',
            ),
            $this->operationDataset(
                'Conference',
                $series(ConferenceBooking::class),
                '#0EA5E9',
                'rgba(14, 165, 233, 0.18)',
            ),
            $this->operationDataset(
                'Restaurant Reservations',
                $series(RestaurantReservation::class),
                '#F59E0B',
                'rgba(245, 158, 11, 0.18)',
            ),
            $this->operationDataset(
                'Food Orders',
                $series(RestaurantOrder::class),
                '#10B981',
                'rgba(16, 185, 129, 0.18)',
            ),
        ], 'labels' => $buckets->pluck('label')->all()];
    }

    /**
     * Builds a consistently styled operational chart dataset.
     *
     * @param  array<int, int>  $data
     * @return array<string, mixed>
     */
    private function operationDataset(string $label, array $data, string $color, string $backgroundColor): array
    {
        return [
            'label' => $label,
            'data' => $data,
            'borderColor' => $color,
            'backgroundColor' => $backgroundColor,
            'pointBackgroundColor' => $color,
            'pointBorderColor' => '#FFFFFF',
            'pointRadius' => 3,
            'borderWidth' => 3,
            'tension' => 0.35,
            'fill' => true,
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
     * Selects a readable bucket size without creating excessive chart points.
     */
    private function granularityFor(Carbon $start, Carbon $end): string
    {
        $days = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay());
        $months = $start->copy()->startOfMonth()->diffInMonths($end->copy()->startOfMonth());

        return match (true) {
            $days <= 1 => 'hour',
            $days <= 62 => 'day',
            $months <= 60 => 'month',
            default => 'year',
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
            'month' => $start->copy()->startOfMonth(),
            default => $start->copy()->startOfYear(),
        };
        $buckets = collect();

        while ($cursor->lessThanOrEqualTo($end)) {
            $buckets->push([
                'key' => $this->bucketKey($cursor, $granularity),
                'label' => match ($granularity) {
                    'hour' => $cursor->format('g A'),
                    'day' => $period === 'weekly' ? $cursor->format('D M j') : $cursor->format('M j'),
                    'month' => $cursor->format('M Y'),
                    default => $cursor->format('Y'),
                },
            ]);

            match ($granularity) {
                'hour' => $cursor->addHour(),
                'day' => $cursor->addDay(),
                'month' => $cursor->addMonth(),
                default => $cursor->addYear(),
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
    private function operationTotals(string $model, string $dateColumn, Carbon $start, Carbon $end, string $granularity): array
    {
        $expression = $this->bucketExpression($model, $dateColumn, $granularity);
        $query = $this->validOperationsQuery($model);
        $range = $dateColumn === 'created_at'
            ? [$start, $end]
            : [$start->toDateString(), $end->toDateString()];

        return $query
            ->whereBetween($dateColumn, $range)
            ->selectRaw("{$expression} as bucket")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw($expression)
            ->pluck('total', 'bucket')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    /**
     * Excludes records that no longer represent valid operational activity.
     *
     * @param  class-string  $model
     */
    private function validOperationsQuery(string $model): Builder
    {
        $query = $model::query();

        return match ($model) {
            Booking::class => $query->whereNotIn('status', ['cancelled', 'expired', 'no_show']),
            ConferenceBooking::class => $query->where('status', '!=', 'cancelled'),
            RestaurantReservation::class => $query->whereNotIn('status', ['cancelled', 'no_show']),
            RestaurantOrder::class => $query->where('status', '!=', 'cancelled'),
        };
    }

    /**
     * Returns a database-specific expression for each supported bucket size.
     *
     * @param  class-string  $model
     */
    private function bucketExpression(string $model, string $column, string $granularity): string
    {
        $driver = $model::query()->getModel()->getConnection()->getDriverName();

        return match ($driver) {
            'sqlite' => match ($granularity) {
                'hour' => "strftime('%Y-%m-%d %H:00:00', {$column})",
                'day' => "strftime('%Y-%m-%d', {$column})",
                'month' => "strftime('%Y-%m-01', {$column})",
                default => "strftime('%Y-01-01', {$column})",
            },
            'pgsql' => match ($granularity) {
                'hour' => "to_char(date_trunc('hour', {$column}), 'YYYY-MM-DD HH24:00:00')",
                'day' => "to_char({$column}, 'YYYY-MM-DD')",
                'month' => "to_char({$column}, 'YYYY-MM-01')",
                default => "to_char({$column}, 'YYYY-01-01')",
            },
            default => match ($granularity) {
                'hour' => "DATE_FORMAT({$column}, '%Y-%m-%d %H:00:00')",
                'day' => "DATE_FORMAT({$column}, '%Y-%m-%d')",
                'month' => "DATE_FORMAT({$column}, '%Y-%m-01')",
                default => "DATE_FORMAT({$column}, '%Y-01-01')",
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
            'month' => $cursor->format('Y-m-01'),
            default => $cursor->format('Y-01-01'),
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
