<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\BookingCalendar;
use App\Filament\Admin\Pages\CorporateReceivables;
use Tests\TestCase;

class FilamentPageHierarchyTest extends TestCase
{
    public function test_custom_pages_do_not_repeat_the_filament_page_title(): void
    {
        $calendar = file_get_contents(resource_path('views/filament/admin/pages/booking-calendar.blade.php'));
        $receivables = file_get_contents(resource_path('views/filament/admin/pages/corporate-receivables.blade.php'));

        self::assertStringNotContainsString('<h1', $calendar);
        self::assertStringNotContainsString('<h1', $receivables);
        self::assertNotEmpty((new BookingCalendar)->getSubheading());
        self::assertNotEmpty((new CorporateReceivables)->getSubheading());
    }
}
