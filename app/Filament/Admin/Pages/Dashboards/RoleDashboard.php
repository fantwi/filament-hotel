<?php

namespace App\Filament\Admin\Pages\Dashboards;

use App\Models\User;
use App\Services\StaffAccountAccess;
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

        abort_unless($user instanceof User, 403, 'You do not have access to an admin dashboard.');

        $dashboardRouteName = app(StaffAccountAccess::class)->dashboardRouteName($user);

        abort_unless($dashboardRouteName, 403, 'You do not have access to an admin dashboard.');
        $this->redirect(route($dashboardRouteName));
    }

    /**
     * Builds and returns widgets.
     */
    public function getWidgets(): array
    {
        return [];
    }
}
