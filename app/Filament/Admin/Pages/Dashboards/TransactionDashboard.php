<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\TransactionOverview;

class TransactionDashboard extends TimeFilteredDashboard
{
    protected static string $routePath = 'transaction-dashboard';

    protected static ?string $title = 'Transaction Dashboard';

    protected static ?string $navigationLabel = 'Transaction Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [TransactionOverview::class];
    }
}
