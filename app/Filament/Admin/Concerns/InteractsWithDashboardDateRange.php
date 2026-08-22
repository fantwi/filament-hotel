<?php

namespace App\Filament\Admin\Concerns;

use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

/**
 * Supports the interacts with dashboard date range Filament administration feature.
 */
trait InteractsWithDashboardDateRange
{
    use InteractsWithPageFilters;

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function dashboardDateRange(): array
    {
        $filters = $this->pageFilters ?? [];
        [$fallbackStart, $fallbackEnd] = TimeFilteredDashboard::presetRange(
            (string) ($filters['period'] ?? 'monthly'),
        );

        try {
            $start = filled($filters['start_date'] ?? null)
                ? Carbon::parse($filters['start_date'])->startOfDay()
                : $fallbackStart;
            $end = filled($filters['end_date'] ?? null)
                ? Carbon::parse($filters['end_date'])->endOfDay()
                : $fallbackEnd;
        } catch (\Throwable) {
            return [$fallbackStart, $fallbackEnd];
        }

        return $start->greaterThan($end) ? [$end->copy()->startOfDay(), $start->copy()->endOfDay()] : [$start, $end];
    }

    /**
     * Configures for dashboard date range for the Filament administration interface.
     */
    protected function forDashboardDateRange(Builder $query, string $column = 'created_at'): Builder
    {
        [$start, $end] = $this->dashboardDateRange();

        return $query->whereBetween($column, [$start, $end]);
    }

    /**
     * Configures dashboard date range label for the Filament administration interface.
     */
    protected function dashboardDateRangeLabel(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return $start->isSameDay($end)
            ? $start->format('M j, Y')
            : $start->format('M j, Y').' - '.$end->format('M j, Y');
    }
}
