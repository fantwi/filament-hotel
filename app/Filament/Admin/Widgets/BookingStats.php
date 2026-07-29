<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            //
            Stat::make('Today Check-ins', Booking::whereDate('check_in', today())->count()),
            Stat::make('Today Check-outs', Booking::whereDate('check_out', today())->count()),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin', 'receptionist']);
    }
}
