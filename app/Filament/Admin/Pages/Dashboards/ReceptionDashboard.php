<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\ReceptionArrivals;
use App\Filament\Admin\Widgets\ReceptionStats;
use Filament\Pages\Dashboard;

class ReceptionDashboard extends Dashboard
{
    protected static string $routePath = 'reception-dashboard';

    protected static ?string $title = 'Reception Dashboard';

    protected static ?string $navigationLabel = 'Reception Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view reception dashboard') ?? false;
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'xl' => 3];
    }

    public function getWidgets(): array
    {
        return [ReceptionStats::class, ReceptionArrivals::class];
    }
}
