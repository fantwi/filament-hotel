<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\BestSellingMenuItems;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\RestaurantOrderStatusChart;
use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use App\Filament\Admin\Widgets\SuperAdminStats;
use Filament\Pages\Dashboard;

class SuperAdminDashboard extends Dashboard
{
    protected static string $routePath = 'super-admin-dashboard';

    protected static ?string $title = 'Super Admin Dashboard';

    protected static ?string $navigationLabel = 'Super Admin Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view super admin dashboard') ?? false;
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'xl' => 3];
    }

    public function getWidgets(): array
    {
        return [SuperAdminStats::class, KitchenStockStats::class, RestaurantRevenueChart::class, RestaurantOrderStatusChart::class, BestSellingMenuItems::class, KitchenOrderQueue::class];
    }
}
