<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Filament\Admin\Widgets\AdminStats;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\ManagerOperationsChart;
use App\Filament\Admin\Widgets\RecentPayments;
use Filament\Pages\Dashboard;

class AdminDashboard extends Dashboard
{
    protected static string $routePath = 'admin-dashboard';
    protected static ?string $title = 'Admin Dashboard';
    protected static ?string $navigationLabel = 'Admin Dashboard';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool { return auth()->user()?->can('view admin dashboard') ?? false; }
    public function getColumns(): int|array { return ['default' => 1, 'md' => 2, 'xl' => 3]; }
    public function getWidgets(): array { return [AdminStats::class, ManagerOperationsChart::class, RecentPayments::class, KitchenOrderQueue::class]; }
}
