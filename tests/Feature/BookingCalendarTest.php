<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\BookingCalendar;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\Guest;
use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_calendar_is_available_to_reservation_staff(): void
    {
        self::assertFalse(BookingCalendar::canAccess());

        foreach (['super_admin', 'admin', 'management', 'accounting', 'reception'] as $department) {
            $user = User::factory()->create(['department' => $department]);

            $this->actingAs($user);
            self::assertTrue(BookingCalendar::canAccess(), "{$department} should access the booking calendar.");

            auth()->logout();
        }
    }

    public function test_booking_calendar_events_endpoint_is_available_to_reservation_staff(): void
    {
        $manager = User::factory()->create(['department' => 'management']);

        $response = $this->actingAs($manager)->getJson(route('admin.calendar-events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->addMonth()->startOfMonth()->toDateString(),
        ]));

        $response->assertOk()->assertJsonCount(0);
    }

    public function test_booking_calendar_page_renders_with_a_sidebar_navigation_entry(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);

        $response = $this->actingAs($admin)->get(route('filament.admin.pages.booking-calendar'));

        $response->assertOk();
        $response->assertSee('Booking calendar');
        $response->assertSee('Reservations');
        self::assertTrue(BookingCalendar::shouldRegisterNavigation());
        self::assertSame('Accommodation', BookingCalendar::getNavigationGroup());
        self::assertSame(50, BookingCalendar::getNavigationSort());
    }

    public function test_booking_calendar_view_has_responsive_sidebar_and_calendar_states(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/pages/booking-calendar.blade.php'));
        $calendarModule = file_get_contents(resource_path('js/calendar.js'));
        $layoutHelper = file_get_contents(resource_path('js/booking-calendar-layout.js'));
        $startupModule = file_get_contents(resource_path('js/booking-calendar-startup.js'));
        $viteConfig = file_get_contents(base_path('vite.config.js'));

        self::assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_18rem]', $view);
        self::assertStringContainsString('lg:sticky lg:top-6', $view);
        self::assertStringContainsString('grid-cols-1 gap-2 text-center text-xs sm:grid-cols-3', $view);
        self::assertStringContainsString('Calendar guide', $view);
        self::assertStringContainsString('No reservations in this range', $view);
        self::assertStringContainsString('Calendar unavailable', $view);
        self::assertStringContainsString('aria-label="Booking calendar"', $view);
        self::assertStringContainsString('booking-calendar-event-modal', $view);
        self::assertStringContainsString('Open reservation record', $view);
        self::assertStringContainsString('Payment', $view);
        self::assertStringContainsString('booking-calendar-event-selected', $view);
        self::assertStringContainsString('window.calendarLayout(window.innerWidth)', $view);
        self::assertStringContainsString('calendar.changeView(nextLayout.initialView)', $view);
        self::assertStringContainsString("calendar.setOption('headerToolbar', nextLayout.headerToolbar)", $view);
        self::assertStringContainsString("import { calendarLayout } from './booking-calendar-layout';", $calendarModule);
        self::assertStringContainsString('window.calendarLayout = calendarLayout;', $calendarModule);
        self::assertStringContainsString('synchronizeBookingCalendarStartup(window);', $calendarModule);
        self::assertStringContainsString("BOOKING_CALENDAR_MODULE_READY_EVENT = 'booking-calendar-module-ready'", $startupModule);
        self::assertStringContainsString("initialView: 'dayGridDay'", $layoutHelper);
        self::assertStringNotContainsString('window.alert', $view);
        self::assertStringNotContainsString('window.location.assign', $view);
        self::assertStringNotContainsString("window.innerWidth < 640 ? 'dayGridWeek'", $view);
        self::assertStringNotContainsString("showState('error', 'Calendar library unavailable')", $view);
        self::assertStringContainsString('livewire:navigating', $view);
        self::assertStringContainsString('element.__bookingCalendar.destroy()', $view);
        self::assertStringContainsString("@vite('resources/js/calendar.js')", $view);
        self::assertStringNotContainsString("@vite('resources/js/app.js')", $view);
        self::assertStringContainsString("'resources/js/calendar.js'", $viteConfig);
    }

    public function test_events_include_hotel_conference_and_restaurant_records(): void
    {
        $manager = User::factory()->create(['department' => 'management']);
        $guest = Guest::query()->create([
            'first_name' => 'Calendar',
            'last_name' => 'Guest',
            'email' => 'calendar-guest@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Calendar room type',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'CAL-1',
            'status' => 'available',
        ]);
        Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => now()->startOfMonth()->addDay(),
            'check_out' => now()->startOfMonth()->addDays(3),
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Calendar conference room',
            'capacity' => 10,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => now()->startOfMonth()->addDay(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $restaurant = Restaurant::query()->create([
            'name' => 'Calendar restaurant',
            'description' => 'Calendar test restaurant',
            'opening_time' => '09:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'T-1',
            'capacity' => 4,
        ]);
        RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => 'Calendar Guest',
            'guest_email' => 'calendar-guest@example.test',
            'guest_phone' => '0240000000',
            'reservation_date' => now()->startOfMonth()->addDay(),
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);

        $response = $this->actingAs($manager)->getJson(route('admin.calendar-events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->addMonth()->startOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $events = collect($response->json());

        self::assertSame(
            ['conference', 'hotel', 'restaurant'],
            $events->pluck('id')->map(fn (string $id): string => str($id)->before('-')->toString())->sort()->values()->all(),
        );
        self::assertSame('Calendar conference room', $events->firstWhere('id', 'conference-1')['title']);
        self::assertSame('T-1', $events->firstWhere('id', 'restaurant-1')['title']);
        self::assertSame('paid', $events->firstWhere('id', 'hotel-1')['extendedProps']['payment_status']);
        self::assertSame('paid', $events->firstWhere('id', 'conference-1')['extendedProps']['payment_status']);
        self::assertSame('completed', $events->firstWhere('id', 'restaurant-1')['extendedProps']['payment_status']);
    }
}
