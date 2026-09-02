<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\RestaurantOrder;
use Filament\Widgets\ChartWidget;

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
        $labels = [];
        $revenueData = [];
        $orderCountData = [];

        for ($month = $start->copy()->startOfMonth(); $month->lessThanOrEqualTo($end); $month->addMonth()) {
            $monthStart = $month->copy()->max($start);
            $monthEnd = $month->copy()->endOfMonth()->min($end);
            $labels[] = $month->format('M Y');

            $monthQuery = RestaurantOrder::query()->whereBetween('created_at', [$monthStart, $monthEnd]);

            $revenueData[] = (float) (clone $monthQuery)
                ->where('payment_status', 'completed')
                ->sum('total');
            $orderCountData[] = (clone $monthQuery)->count();
        }

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
