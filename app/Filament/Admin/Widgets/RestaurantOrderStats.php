<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RestaurantOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RestaurantOrderStats extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalOrders = RestaurantOrder::count();
        $ordersToday = RestaurantOrder::query()->whereDate('created_at', today())->count();
        $pendingOrders = RestaurantOrder::query()->where('status', 'pending')->count();
        $activeKitchenOrders = RestaurantOrder::query()
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->count();
        $completedRevenue = RestaurantOrder::query()
            ->where('payment_status', 'completed')
            ->sum('total');
        $outstandingRevenue = RestaurantOrder::query()
            ->where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled')
            ->sum('total');
        $averageOrderValue = RestaurantOrder::query()
            ->where('payment_status', 'completed')
            ->avg('total') ?? 0;

        return [
            Stat::make('Total Food Orders', number_format($totalOrders))
                ->description(number_format($ordersToday) . ' created today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-shopping-bag')
                ->color('primary'),
            Stat::make('Pending Orders', number_format($pendingOrders))
                ->description('Awaiting payment or confirmation')
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Kitchen Queue', number_format($activeKitchenOrders))
                ->description('Confirmed, preparing, or ready')
                ->icon('heroicon-o-fire')
                ->color('info'),
            Stat::make('Completed Revenue', 'GHS ' . number_format($completedRevenue, 2))
                ->description('Revenue from completed payments')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Outstanding Revenue', 'GHS ' . number_format($outstandingRevenue, 2))
                ->description('Unpaid active orders')
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger'),
            Stat::make('Average Order Value', 'GHS ' . number_format($averageOrderValue, 2))
                ->description('Average paid order')
                ->icon('heroicon-o-calculator')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
