<?php

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Provides the room timeline Filament administration page.
 */
class RoomTimeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected string $view = 'filament.admin.pages.redirecting';

    protected static ?string $navigationLabel = 'Room Timeline';

    protected static ?string $title = 'Room Timeline Board';

    /**
     * Controls whether this feature appears in the Filament navigation.
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
