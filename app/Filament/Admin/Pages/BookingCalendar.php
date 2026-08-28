<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

/**
 * Provides the booking calendar Filament administration page.
 */
class BookingCalendar extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Booking Calendar';

    protected static ?string $title = 'Booking Calendar';

    protected static string|\UnitEnum|null $navigationGroup = 'Reservations';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.admin.pages.booking-calendar';

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'manager',
            'accountant',
            'receptionist',
        ]) ?? false;
    }
}
