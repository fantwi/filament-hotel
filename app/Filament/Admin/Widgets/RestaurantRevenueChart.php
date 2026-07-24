<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RestaurantOrder;
use Filament\Widgets\ChartWidget;

class RestaurantRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Restaurant Revenue — Last 12 Months';

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

    protected function getData(): array
    {
        $labels = [];
        $revenueData = [];
        $orderCountData = [];

        for ($monthsAgo = 11; $monthsAgo >= 0; $monthsAgo--) {
            $month = now()->subMonths($monthsAgo)->startOfMonth();
            $labels[] = $month->format('M Y');

            $monthQuery = RestaurantOrder::query()->whereBetween('created_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ]);

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
