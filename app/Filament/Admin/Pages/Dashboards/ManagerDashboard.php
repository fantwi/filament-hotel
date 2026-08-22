<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\CorporateBillingOverview;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenProductionStats;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\ManagerOperationsChart;
use App\Filament\Admin\Widgets\ManagerPeriodReport;
use App\Filament\Admin\Widgets\ManagerStats;
use App\Filament\Admin\Widgets\RestaurantOrderStatusChart;
use App\Filament\Admin\Widgets\RoleDashboardOverview;

/**
 * Provides the manager dashboard Filament administration page.
 */
class ManagerDashboard extends TimeFilteredDashboard
{
    protected static string $routePath = 'manager-dashboard';

    protected static ?string $title = 'Manager Dashboard';

    protected static ?string $navigationLabel = 'Manager Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 4;

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('view manager dashboard') ?? false;
    }

    /**
     * Builds and returns columns.
     */
    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'xl' => 3];
    }

    /**
     * Builds and returns widgets.
     */
    public function getWidgets(): array
    {
        return [
            RoleDashboardOverview::class,
            ManagerStats::class,
            ManagerPeriodReport::class,
            CorporateBillingOverview::class,
            KitchenProductionStats::class,
            KitchenStockStats::class,
            ManagerOperationsChart::class,
            RestaurantOrderStatusChart::class,
            KitchenOrderQueue::class,
        ];
    }
}
