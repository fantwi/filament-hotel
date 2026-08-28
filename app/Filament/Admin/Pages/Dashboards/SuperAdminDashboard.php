<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\BestSellingMenuItems;
use App\Filament\Admin\Widgets\CorporateBillingOverview;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\RestaurantOrderStatusChart;
use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use App\Filament\Admin\Widgets\RoleDashboardOverview;
use App\Filament\Admin\Widgets\SuperAdminFinanceStats;
use App\Filament\Admin\Widgets\SuperAdminOperationsStats;
use App\Filament\Admin\Widgets\SuperAdminStats;

/**
 * Provides the super admin dashboard Filament administration page.
 */
class SuperAdminDashboard extends TimeFilteredDashboard
{
    protected static string $routePath = 'super-admin-dashboard';

    protected static ?string $title = 'Super Admin Dashboard';

    protected static ?string $navigationLabel = 'Super Admin Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 1;

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('view super admin dashboard') ?? false;
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
            SuperAdminStats::class,
            SuperAdminOperationsStats::class,
            SuperAdminFinanceStats::class,
            CorporateBillingOverview::class,
            KitchenStockStats::class,
            RestaurantRevenueChart::class,
            RestaurantOrderStatusChart::class,
            BestSellingMenuItems::class,
            KitchenOrderQueue::class,
        ];
    }
}
