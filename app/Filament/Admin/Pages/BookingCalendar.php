<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class BookingCalendar extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';
    protected string $view = 'filament.admin.pages.booking-calendar';
    protected static ?string $navigationLabel = 'Booking Calendar';
    protected static ?int $navigationSort = 3;
}
