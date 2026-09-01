<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

/**
 * Provides the room calendar Filament administration page.
 */
class RoomCalendar extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected string $view = 'filament.admin.pages.redirecting';

    protected static ?string $navigationLabel = 'Room Calendar';

    protected static ?string $title = 'Room Availability';

    protected static string|\UnitEnum|null $navigationGroup = 'Accommodation';

    protected static ?int $navigationSort = 40;

    /**
     * Keeps the obsolete calendar out of the sidebar while its URL remains
     * available as a compatibility redirect for saved bookmarks.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /**
     * Redirects saved legacy URLs to the maintained unified booking calendar.
     */
    public function mount(): void
    {
        $this->redirect(BookingCalendar::getUrl());
    }
}
