<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\AdminFinanceStats;
use App\Filament\Admin\Widgets\AdminServiceStats;
use App\Filament\Admin\Widgets\CorporateBillingOverview;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\ManagerOperationsChart;
use App\Filament\Admin\Widgets\RecentPayments;
use App\Filament\Admin\Widgets\RoleDashboardOverview;

/**
 * Provides the admin dashboard Filament administration page.
 */
class AdminDashboard extends TimeFilteredDashboard
{
    protected static string $routePath = 'admin-dashboard';

    protected static ?string $title = 'Admin Dashboard';

    protected static ?string $navigationLabel = 'Admin Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 2;

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('view admin dashboard') ?? false;
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
            AdminServiceStats::class,
            AdminFinanceStats::class,
            CorporateBillingOverview::class,
            KitchenStockStats::class,
            ManagerOperationsChart::class,
            RecentPayments::class,
            KitchenOrderQueue::class,
        ];
    }

    /**
     * Keeps the admin command center and its core service and finance metrics
     * above the domain-specific dashboard tabs.
     */
    protected function isPriorityDashboardWidget(string $widgetClass): bool
    {
        return in_array($widgetClass, [
            RoleDashboardOverview::class,
            AdminServiceStats::class,
            AdminFinanceStats::class,
        ], true);
    }

    /**
     * Keeps kitchen stock with the kitchen queue instead of the executive KPIs.
     */
    protected function dashboardSectionForWidget(string $widgetClass): string
    {
        return match ($widgetClass) {
            KitchenStockStats::class => 'Kitchen',
            default => parent::dashboardSectionForWidget($widgetClass),
        };
    }
}
