<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\ReceptionArrivals;
use App\Filament\Admin\Widgets\ReceptionDeskStats;
use App\Filament\Admin\Widgets\ReceptionStats;
use App\Filament\Admin\Widgets\RoleDashboardOverview;

/**
 * Provides the reception dashboard Filament administration page.
 */
class ReceptionDashboard extends TimeFilteredDashboard
{
    protected static string $routePath = 'reception-dashboard';

    protected static ?string $title = 'Reception Dashboard';

    protected static ?string $navigationLabel = 'Reception Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 5;

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('view reception dashboard') ?? false;
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
        return [RoleDashboardOverview::class, ReceptionStats::class, ReceptionDeskStats::class, ReceptionArrivals::class];
    }
}
