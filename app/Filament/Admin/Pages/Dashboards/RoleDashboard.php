<?php

namespace App\Filament\Admin\Pages\Dashboards;

use Filament\Pages\Dashboard;

class RoleDashboard extends Dashboard
{
    protected static string $routePath = '/';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $user = auth()->user();

        $dashboard = match (true) {
            $user?->hasRole('super_admin') => SuperAdminDashboard::class,
            $user?->hasRole('admin') => AdminDashboard::class,
            $user?->hasRole('accountant') => AccountantDashboard::class,
            $user?->hasRole('manager') => ManagerDashboard::class,
            $user?->hasRole('receptionist') => ReceptionDashboard::class,
            default => null,
        };

        abort_unless($dashboard, 403, 'You do not have access to an admin dashboard.');
        $this->redirect($dashboard::getUrl());
    }

    public function getWidgets(): array
    {
        return [];
    }
}
