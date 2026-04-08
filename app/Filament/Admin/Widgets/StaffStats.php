<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StaffStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            // total staff accounts
            Stat::make('Total Staff', User::count())
                ->description('Registered staff accounts')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary'),

            // staff status
            Stat::make('Online Staff', User::where('status','online')->count()),
            Stat::make('Offline Staff', User::where('status','offline')->count()),
            Stat::make('On Leave', User::where('status','on_leave')->count()),
            Stat::make('Suspended', User::where('status','suspended')->count()),

            // staff shift
            Stat::make('Morning Shift', User::where('shift','morning')->count()),
            Stat::make('Evening Shift', User::where('shift','evening')->count()),
            Stat::make('Night Shift', User::where('shift','night')->count()),
            Stat::make('Off Duty', User::where('shift','off_duty')->count()),

            // managers
            Stat::make('Managers', User::role('manager')->count())
                ->icon('heroicon-o-briefcase')
                ->color('secondary'),

            // receptionists
            Stat::make('Receptionists', User::role('receptionist')->count())
                ->icon('heroicon-o-user')
                ->color('success'),
    
            // housekeeping
            Stat::make('Housekeeping', User::role('housekeeping')->count())
                ->icon('heroicon-o-sparkles')
                ->color('info'),

            // accountants
            Stat::make('Accountants', User::role('accountant')->count())
                ->icon('heroicon-o-banknotes')
                ->color('gray'),
        ];
    }

    // only show the widget to super admins, admins, and managers
    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin', 'manager']);
    }
}
