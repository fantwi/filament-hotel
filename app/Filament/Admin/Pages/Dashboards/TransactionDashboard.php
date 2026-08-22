<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\TransactionOverview;

/**
 * Provides the transaction dashboard Filament administration page.
 */
class TransactionDashboard extends TimeFilteredDashboard
{
    protected static string $routePath = 'transaction-dashboard';

    protected static ?string $title = 'Transaction Dashboard';

    protected static ?string $navigationLabel = 'Transaction Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 6;

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Builds and returns columns.
     */
    public function getColumns(): int|array
    {
        return 1;
    }

    /**
     * Builds and returns widgets.
     */
    public function getWidgets(): array
    {
        return [TransactionOverview::class];
    }
}
