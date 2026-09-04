<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenProductionStats;
use App\Filament\Admin\Widgets\KitchenStaffStats;

/**
 * Provides the kitchen staff dashboard Filament administration page.
 */
class KitchenStaffDashboard extends TimeFilteredDashboard
{
    protected static string $routePath = 'kitchen-staff-dashboard';

    protected static ?string $title = 'Kitchen Staff Dashboard';

    protected static ?string $navigationLabel = 'Kitchen Staff Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 7;

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->hasAnyRole(['super_admin', 'admin', 'kitchen_staff'])
            && $user?->can('view kitchen dashboard'));
    }

    /**
     * Builds and returns columns.
     */
    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'xl' => 3];
    }

    /**
     * Keeps the current workload summary above the live queue while moving
     * historical production statistics into the Kitchen section.
     */
    protected function isPriorityDashboardWidget(string $widgetClass): bool
    {
        return $widgetClass === KitchenStaffStats::class;
    }

    /**
     * Clarifies that the reporting period does not limit the live work queue.
     */
    protected function dashboardPeriodDescription(): string
    {
        return 'Controls served-order and finished-food production metrics. The live kitchen queue always shows current active orders.';
    }

    /**
     * Keeps the live workload closer to the top of the staff dashboard.
     */
    protected function dashboardPeriodFiltersStartCollapsed(): bool
    {
        return true;
    }

    /**
     * Builds and returns widgets.
     */
    public function getWidgets(): array
    {
        return [
            KitchenStaffStats::class,
            KitchenOrderQueue::class,
            KitchenProductionStats::class,
        ];
    }
}
