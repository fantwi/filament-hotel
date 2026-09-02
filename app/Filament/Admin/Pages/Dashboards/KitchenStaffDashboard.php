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
