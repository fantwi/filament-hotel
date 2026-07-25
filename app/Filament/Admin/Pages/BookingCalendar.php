<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class BookingCalendar extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Booking Calendar';
    protected static ?string $title = 'Booking Calendar';
    protected static string|\UnitEnum|null $navigationGroup = 'Reservations';
    protected static ?int $navigationSort = 20;
    protected string $view = 'filament.admin.pages.booking-calendar';
}
