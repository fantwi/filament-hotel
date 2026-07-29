<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StaffStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        // Receptionist dashboard
        if ($user->hasRole('receptionist')) {
            return [
                Stat::make('Today Check-ins', Booking::whereDate('check_in', today())->count()),
                Stat::make('Available Rooms', Room::where('status', 'available')->count()),
            ];
        }

        // Accountant dashboard
        if ($user->hasRole('accountant')) {
            return [
                Stat::make('Today Revenue', Payment::whereDate('created_at', today())->sum('amount')),
                Stat::make('Outstanding Balance', Booking::sum('balance')),
            ];
        }

        // Admin / Manager dashboard
        return [
            Stat::make('Total Bookings', Booking::count()),
            Stat::make('Total Revenue', Payment::sum('amount')),
            Stat::make('Guests', Guest::count()),
        ];

        // return [
        //     // total staff accounts
        //     Stat::make('Total Staff', User::count())
        //         ->description('Registered staff accounts')
        //         ->descriptionIcon('heroicon-o-user-group')
        //         ->color('primary'),

        //     // staff status
        //     Stat::make('Online Staff', User::where('status','online')->count()),
        //     Stat::make('Offline Staff', User::where('status','offline')->count()),
        //     Stat::make('On Leave', User::where('status','on_leave')->count()),
        //     Stat::make('Suspended', User::where('status','suspended')->count()),

        //     // staff shift
        //     Stat::make('Morning Shift', User::where('shift','morning')->count()),
        //     Stat::make('Evening Shift', User::where('shift','evening')->count()),
        //     Stat::make('Night Shift', User::where('shift','night')->count()),
        //     Stat::make('Off Duty', User::where('shift','off_duty')->count()),

        //     // managers
        //     Stat::make('Managers', User::role('manager')->count())
        //         ->icon('heroicon-o-briefcase')
        //         ->color('secondary'),

        //     // receptionists
        //     Stat::make('Receptionists', User::role('receptionist')->count())
        //         ->icon('heroicon-o-user')
        //         ->color('success'),

        //     // housekeeping
        //     Stat::make('Housekeeping', User::role('housekeeping')->count())
        //         ->icon('heroicon-o-sparkles')
        //         ->color('info'),

        //     // accountants
        //     Stat::make('Accountants', User::role('accountant')->count())
        //         ->icon('heroicon-o-banknotes')
        //         ->color('gray'),
        // ];
    }

    // only show the widget to super admins, admins, and managers
    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin', 'manager']);
    }
}
