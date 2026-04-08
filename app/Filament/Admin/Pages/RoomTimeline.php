<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class RoomTimeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;
    protected string $view = 'filament.admin.pages.room-timeline';
    protected static ?string $navigationLabel = 'Room Timeline';
    protected static ?string $title = 'Room Timeline Board';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
