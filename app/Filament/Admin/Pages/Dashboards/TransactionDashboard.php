<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\TransactionOverview;
use App\Filament\Admin\Widgets\TransactionStats;

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
        return auth()->user()?->can('view transaction dashboard') ?? false;
    }

    /**
     * Builds and returns columns.
     */
    public function getColumns(): int|array
    {
        return 1;
    }

    /**
     * Describes this selector accurately because it changes the reporting range.
     */
    protected function dashboardPeriodFilterLabel(): string
    {
        return 'Period';
    }

    /**
     * Clarifies that the selector does not group results into chart buckets.
     */
    protected function dashboardPeriodFilterHelpText(): string
    {
        return 'Sets the reporting date range; it does not group results into a time series.';
    }

    /**
     * Builds and returns widgets.
     */
    public function getWidgets(): array
    {
        return [TransactionStats::class, TransactionOverview::class];
    }
}
