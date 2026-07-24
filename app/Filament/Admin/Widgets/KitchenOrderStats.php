<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RestaurantOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KitchenOrderStats extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $averagePreparationMinutes = RestaurantOrder::query()
            ->whereNotNull('preparing_at')
            ->whereNotNull('ready_at')
            ->get()
            ->avg(fn (RestaurantOrder $order): float => $order->preparing_at->diffInMinutes($order->ready_at)) ?? 0;

        return [
            Stat::make('Waiting to Start', RestaurantOrder::paid()->where('status', 'confirmed')->count())
                ->description('Paid and confirmed')->icon('heroicon-o-clock')->color('info'),
            Stat::make('Preparing', RestaurantOrder::where('status', 'preparing')->count())
                ->description('Currently in the kitchen')->icon('heroicon-o-fire')->color('warning'),
            Stat::make('Ready', RestaurantOrder::where('status', 'ready')->count())
                ->description('Waiting to be served')->icon('heroicon-o-bell-alert')->color('success'),
            Stat::make('Served Today', RestaurantOrder::where('status', 'served')->whereDate('served_at', today())->count())
                ->description('Completed service today')->icon('heroicon-o-check-circle')->color('gray'),
            Stat::make('Average Preparation', number_format($averagePreparationMinutes, 0) . ' mins')
                ->description('Preparing to ready')->icon('heroicon-o-chart-bar')->color('primary'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen dashboard') ?? false;
    }
}
