<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\RestaurantOrder;
use Filament\Widgets\ChartWidget;

/**
 * Provides the restaurant order status chart Filament dashboard widget.
 */
class RestaurantOrderStatusChart extends ChartWidget
{
    use InteractsWithDashboardDateRange;

    protected ?string $heading = 'Order Status Distribution';

    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    /**
     * Builds and returns data.
     */
    protected function getData(): array
    {
        $statuses = [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'ready' => 'Ready',
            'served' => 'Served',
            'cancelled' => 'Cancelled',
        ];

        $counts = $this->forDashboardDateRange(RestaurantOrder::query())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'datasets' => [[
                'label' => 'Orders',
                'data' => collect(array_keys($statuses))
                    ->map(fn (string $status): int => (int) ($counts[$status] ?? 0))
                    ->all(),
            ]],
            'labels' => array_values($statuses),
        ];
    }

    /**
     * Builds and returns type.
     */
    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
