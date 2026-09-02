<?php

namespace App\Services;

use App\Enums\StaffAccountStatus;
use App\Models\User;

final class StaffAccountAccess
{
    public const LEAVE_MESSAGE = 'Your account is on leave. Operational access is paused until your privileges are restored.';

    public const SUSPENSION_MESSAGE = 'Your account has been suspended. Contact an administrator to have your privileges restored.';

    private const DASHBOARD_ROUTES = [
        'kitchen_manager' => 'filament.admin.pages.kitchen-manager-dashboard',
        'kitchen_staff' => 'filament.admin.pages.kitchen-staff-dashboard',
        'super_admin' => 'filament.admin.pages.super-admin-dashboard',
        'admin' => 'filament.admin.pages.admin-dashboard',
        'accountant' => 'filament.admin.pages.accountant-dashboard',
        'manager' => 'filament.admin.pages.manager-dashboard',
        'receptionist' => 'filament.admin.pages.reception-dashboard',
    ];

    public function allows(User $user, ?string $routeName): bool
    {
        if (! $user->isStaff() || $user->status === StaffAccountStatus::Active) {
            return true;
        }

        if (in_array($routeName, ['filament.admin.auth.profile', 'filament.admin.auth.logout'], true)) {
            return true;
        }

        if ($user->status === StaffAccountStatus::Suspended) {
            return false;
        }

        $dashboardRouteName = $this->dashboardRouteName($user);

        return $dashboardRouteName !== null && in_array($routeName, [
            'filament.admin.pages.role-dashboard',
            $dashboardRouteName,
        ], true);
    }

    public function dashboardRouteName(User $user): ?string
    {
        foreach (self::DASHBOARD_ROUTES as $role => $routeName) {
            if ($user->hasRole($role)) {
                return $routeName;
            }
        }

        return null;
    }

    public function destination(User $user): string
    {
        if ($user->status === StaffAccountStatus::Suspended) {
            return route('filament.admin.auth.profile');
        }

        return route($this->dashboardRouteName($user) ?? 'filament.admin.auth.profile');
    }

    public function notice(User $user): ?string
    {
        return match ($user->status) {
            StaffAccountStatus::OnLeave => self::LEAVE_MESSAGE,
            StaffAccountStatus::Suspended => self::SUSPENSION_MESSAGE,
            StaffAccountStatus::Active => null,
        };
    }
}
