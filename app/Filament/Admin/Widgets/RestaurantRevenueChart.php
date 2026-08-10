<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\RestaurantOrder;
use Filament\Widgets\ChartWidget;

class RestaurantRevenueChart extends ChartWidget
{
    use InteractsWithDashboardDateRange;

    protected ?string $heading = 'Restaurant Revenue by Selected Date Range';

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

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
                ['label' => 'Revenue (GHS)', 'data' => $revenueData, 'yAxisID' => 'y'],
                ['label' => 'Orders', 'data' => $orderCountData, 'type' => 'line', 'yAxisID' => 'y1'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

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

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
