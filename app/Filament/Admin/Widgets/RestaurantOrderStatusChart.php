<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RestaurantOrder;
use Filament\Widgets\ChartWidget;

class RestaurantOrderStatusChart extends ChartWidget
{
    protected ?string $heading = 'Order Status Distribution';

    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

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

        $counts = RestaurantOrder::query()
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

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
