<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\AccountantPeriodReport;
use App\Filament\Admin\Widgets\AccountantStats;
use App\Filament\Admin\Widgets\CorporateBillingOverview;
use App\Filament\Admin\Widgets\RecentPayments;
use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use App\Filament\Admin\Widgets\RoleDashboardOverview;

/**
 * Provides the accountant dashboard Filament administration page.
 */
class AccountantDashboard extends TimeFilteredDashboard
{
    protected static string $routePath = 'accountant-dashboard';

    protected static ?string $title = 'Accountant Dashboard';

    protected static ?string $navigationLabel = 'Accountant Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 3;

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('view accountant dashboard') ?? false;
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
            AccountantStats::class,
            AccountantPeriodReport::class,
            CorporateBillingOverview::class,
            RestaurantRevenueChart::class,
            RecentPayments::class,
        ];
    }
}
