<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\BookingCalendar;
use App\Filament\Admin\Resources\Bookings\Pages\ListBookings;
use App\Filament\Admin\Resources\RestaurantReservations\Pages\ListRestaurantReservations;
use App\Filament\Admin\Widgets\AdminFinanceStats;
use App\Filament\Admin\Widgets\AdminServiceStats;
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
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class AdminDashboardDrillDownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_stats_link_to_matching_date_and_status_filtered_destinations(): void
    {
        $filters = [
            'period' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ];

        $service = $this->stats(new AdminServiceStats, $filters);
        $finance = $this->stats(new AdminFinanceStats, $filters);

        $this->assertFilteredLink($service['Active Hotel Bookings'], '/admin/bookings', [
            'filters.active_period.from' => '2026-08-01',
            'filters.active_period.until' => '2026-08-15',
        ]);
        $this->assertFilteredLink($service['Conference Bookings'], '/admin/booking-calendar', [
            'type' => 'conference',
            'status_scope' => 'active',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ]);
        $this->assertFilteredLink($service['Table Reservations'], '/admin/restaurant-reservations', [
            'filters.active_period.from' => '2026-08-01',
            'filters.active_period.until' => '2026-08-15',
        ]);
        $this->assertFilteredLink($service['Kitchen Orders'], '/admin/restaurant-orders', [
            'filters.status.value' => 'kitchen_queue',
            'filters.created_at.created_from' => '2026-08-01',
            'filters.created_at.created_until' => '2026-08-15',
        ]);
        $this->assertFilteredLink($finance['Collected Revenue'], '/admin/payments', [
            'filters.payment_status' => 'collected',
            'filters.start_date' => '2026-08-01',
            'filters.end_date' => '2026-08-15',
        ]);
        $this->assertFilteredLink($finance['Pending Payments'], '/admin/payments', [
            'filters.payment_status' => 'pending',
            'filters.start_date' => '2026-08-01',
            'filters.end_date' => '2026-08-15',
        ]);
        $this->assertFilteredLink($finance['Refunds'], '/admin/payments', [
            'filters.payment_status' => 'refunded',
            'filters.start_date' => '2026-08-01',
            'filters.end_date' => '2026-08-15',
        ]);
        $this->assertFilteredLink($finance['Corporate Outstanding'], '/admin/corporate-receivables');
    }

    public function test_active_period_resource_filters_match_the_service_stat_scopes(): void
    {
        [$guest, $room, $restaurant, $table] = $this->reservationFixture();
        $activeBooking = $this->booking($guest, $room, '2026-07-30', '2026-08-02', 'confirmed');
        $cancelledBooking = $this->booking($guest, $room, '2026-08-04', '2026-08-06', 'cancelled');
        $outsideBooking = $this->booking($guest, $room, '2026-08-16', '2026-08-18', 'confirmed');
        $activeReservation = $this->reservation($guest, $restaurant, $table, '2026-08-05', 'confirmed');
        $completedReservation = $this->reservation($guest, $restaurant, $table, '2026-08-06', 'completed');
        $outsideReservation = $this->reservation($guest, $restaurant, $table, '2026-08-16', 'confirmed');
        $filters = [
            'period' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ];
        $service = $this->stats(new AdminServiceStats, $filters);

        self::assertSame('1', $service['Active Hotel Bookings']->getValue());
        self::assertSame('1', $service['Table Reservations']->getValue());

        $admin = User::factory()->create(['department' => 'admin']);

        Livewire::withQueryParams([
            'filters' => [
                'active_period' => ['from' => '2026-08-01', 'until' => '2026-08-15'],
            ],
        ])->actingAs($admin)
            ->test(ListBookings::class)
            ->assertCanSeeTableRecords([$activeBooking])
            ->assertCanNotSeeTableRecords([$cancelledBooking, $outsideBooking]);

        Livewire::withQueryParams([
            'filters' => [
                'active_period' => ['from' => '2026-08-01', 'until' => '2026-08-15'],
            ],
        ])->actingAs($admin)
            ->test(ListRestaurantReservations::class)
            ->assertCanSeeTableRecords([$activeReservation])
            ->assertCanNotSeeTableRecords([$completedReservation, $outsideReservation]);
    }

    public function test_conference_calendar_drill_down_returns_only_active_conferences_in_the_requested_range(): void
    {
        [$guest, $room] = $this->guestAndRoom();
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Dashboard Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $active = $this->conferenceBooking($guest, $conferenceRoom, '2026-08-05', 'confirmed');
        $this->conferenceBooking($guest, $conferenceRoom, '2026-08-06', 'cancelled');
        $this->conferenceBooking($guest, $conferenceRoom, '2026-08-16', 'confirmed');
        $this->booking($guest, $room, '2026-08-05', '2026-08-07', 'confirmed');
        $admin = User::factory()->create(['department' => 'admin']);

        $response = $this->actingAs($admin)->getJson(route('admin.calendar-events', [
            'start' => '2026-08-01',
            'end' => '2026-09-01',
            'type' => 'conference',
            'status_scope' => 'active',
            'range_start' => '2026-08-01',
            'range_end' => '2026-08-15',
        ]));

        $response->assertOk()->assertJsonCount(1);
        self::assertSame("conference-{$active->id}", $response->json('0.id'));
    }

    public function test_conference_calendar_drill_down_applies_a_visible_calendar_focus(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);

        Livewire::withQueryParams([
            'type' => 'conference',
            'status_scope' => 'active',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ])->actingAs($admin)
            ->test(BookingCalendar::class)
            ->assertSet('eventType', 'conference')
            ->assertSet('statusScope', 'active')
            ->assertSet('focusDate', '2026-08-01')
            ->assertSet('filterEndDate', '2026-08-15')
            ->assertSee('Active conference bookings')
            ->assertSee('Aug 1, 2026 – Aug 15, 2026');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, Stat>
     */
    private function stats(object $widget, array $filters): array
    {
        $widget->pageFilters = $filters;
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    /**
     * @param  array<string, string>  $expectedQuery
     */
    private function assertFilteredLink(Stat $stat, string $path, array $expectedQuery = []): void
    {
        $url = (string) $stat->getUrl();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame($path, parse_url($url, PHP_URL_PATH));
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stat->getDescriptionIcon());

        foreach ($expectedQuery as $key => $value) {
            self::assertSame($value, data_get($query, $key), $key);
        }
    }

    /**
     * @return array{Guest, Room, Restaurant, RestaurantTable}
     */
    private function reservationFixture(): array
    {
        [$guest, $room] = $this->guestAndRoom();
        $restaurant = Restaurant::query()->create([
            'name' => 'Dashboard Restaurant',
            'description' => 'Restaurant fixture for Admin Dashboard drill-downs.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'DASH-T1',
            'capacity' => 4,
        ]);

        return [$guest, $room, $restaurant, $table];
    }

    /**
     * @return array{Guest, Room}
     */
    private function guestAndRoom(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Dashboard',
            'last_name' => 'Guest',
            'email' => 'dashboard-drill-down@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Dashboard Room Type',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'DASH-1',
            'status' => 'available',
        ]);

        return [$guest, $room];
    }

    private function booking(Guest $guest, Room $room, string $checkIn, string $checkOut, string $status): Booking
    {
        return Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_price' => 100,
            'status' => $status,
            'payment_status' => 'pending',
        ]);
    }

    private function reservation(
        Guest $guest,
        Restaurant $restaurant,
        RestaurantTable $table,
        string $date,
        string $status,
    ): RestaurantReservation {
        return RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->full_name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => $date,
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 50,
            'status' => $status,
            'payment_status' => 'pending',
        ]);
    }

    private function conferenceBooking(
        Guest $guest,
        ConferenceRoom $room,
        string $date,
        string $status,
    ): ConferenceBooking {
        return ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $room->id,
            'booking_date' => $date,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => $status,
            'payment_status' => 'pending',
        ]);
    }
}
