<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\BookingCalendar;
use App\Filament\Admin\Pages\RoomCalendar;
use App\Filament\Admin\Pages\RoomTimeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyCalendarRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_room_calendar_redirects_to_the_maintained_booking_calendar(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);

        $this->actingAs($admin)
            ->get(route('filament.admin.pages.room-calendar'))
            ->assertRedirect(BookingCalendar::getUrl());
    }

    public function test_legacy_room_timeline_redirects_to_the_maintained_booking_calendar(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);

        $this->actingAs($admin)
            ->get(route('filament.admin.pages.room-timeline'))
            ->assertRedirect(BookingCalendar::getUrl());
    }

    public function test_legacy_room_pages_are_not_registered_in_navigation(): void
    {
        self::assertFalse(RoomCalendar::shouldRegisterNavigation());
        self::assertFalse(RoomTimeline::shouldRegisterNavigation());
    }
}
