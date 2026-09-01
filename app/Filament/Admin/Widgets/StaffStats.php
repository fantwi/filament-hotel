<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the staff stats Filament dashboard widget.
 */
class StaffStats extends StatsOverviewWidget
{
    /**
     * Builds and returns stats.
     */
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
    }

    // only show the widget to super admins, admins, and managers
    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin', 'manager']);
    }
}
