<?php

namespace App\Filament\Admin\Pages\Dashboards;

use Filament\Pages\Dashboard;

/**
 * Provides the role dashboard Filament administration page.
 */
class RoleDashboard extends Dashboard
{
    protected static string $routePath = '/';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Initializes component state before it is rendered.
     */
    public function mount(): void
    {
        $user = auth()->user();

        $dashboard = match (true) {
            $user?->hasRole('kitchen_manager') => KitchenManagerDashboard::class,
            $user?->hasRole('kitchen_staff') => KitchenStaffDashboard::class,
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

    /**
     * Builds and returns widgets.
     */
    public function getWidgets(): array
    {
        return [];
    }
}
