<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\AccountantStats;
use App\Filament\Admin\Widgets\AccountantPeriodReport;
use App\Filament\Admin\Widgets\RecentPayments;
use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use Filament\Pages\Dashboard;

class AccountantDashboard extends Dashboard
{
    protected static string $routePath = 'accountant-dashboard';
    protected static ?string $title = 'Accountant Dashboard';
    protected static ?string $navigationLabel = 'Accountant Dashboard';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool { return auth()->user()?->can('view accountant dashboard') ?? false; }
    public function getColumns(): int|array { return ['default' => 1, 'md' => 2, 'xl' => 3]; }
    public function getWidgets(): array { return [AccountantStats::class, AccountantPeriodReport::class, RestaurantRevenueChart::class, RecentPayments::class]; }
}
