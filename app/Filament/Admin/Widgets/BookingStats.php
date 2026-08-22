<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the booking stats Filament dashboard widget.
 */
class BookingStats extends StatsOverviewWidget
{
    /**
     * Builds and returns stats.
     */
    protected function getStats(): array
    {
        return [
            //
            Stat::make('Today Check-ins', Booking::whereDate('check_in', today())->count()),
            Stat::make('Today Check-outs', Booking::whereDate('check_out', today())->count()),
        ];
    }

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin', 'receptionist']);
    }
}
