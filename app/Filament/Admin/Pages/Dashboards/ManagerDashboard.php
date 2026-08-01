<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenProductionStats;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\ManagerOperationsChart;
use App\Filament\Admin\Widgets\ManagerPeriodReport;
use App\Filament\Admin\Widgets\ManagerStats;
use App\Filament\Admin\Widgets\RestaurantOrderStatusChart;
use Filament\Pages\Dashboard;

class ManagerDashboard extends Dashboard
{
    protected static string $routePath = 'manager-dashboard';

    protected static ?string $title = 'Manager Dashboard';

    protected static ?string $navigationLabel = 'Manager Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view manager dashboard') ?? false;
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'xl' => 3];
    }

    public function getWidgets(): array
    {
        return [KitchenProductionStats::class, KitchenStockStats::class, ManagerStats::class, ManagerPeriodReport::class, ManagerOperationsChart::class, RestaurantOrderStatusChart::class, KitchenOrderQueue::class];
    }
}
