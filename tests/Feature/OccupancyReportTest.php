<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\OccupancyReport;
use App\Filament\Admin\Widgets\OccupancyStats;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\Guest;
use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OccupancyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_occupancy_report_uses_one_selected_period_for_structured_operational_data(): void
    {
        $reportPage = new OccupancyReport;
        $reportPage->period = 'quarterly';

        self::assertSame('Quarterly', $reportPage->periodLabel());

        $report = $reportPage->report();

        self::assertArrayHasKey('occupancyRate', $report);
        self::assertArrayHasKey('bookedRoomNights', $report);
        self::assertArrayHasKey('conferenceAvailability', $report);
        self::assertArrayHasKey('tableStatus', $report);
    }

    public function test_current_conference_booking_blocks_room_while_future_booking_does_not(): void
    {
        $this->travelTo('2026-09-04 11:00:00');

        $guest = Guest::query()->create([
            'first_name' => 'Conference',
            'last_name' => 'Guest',
            'phone_number' => '0240000010',
            'email' => 'conference-occupancy@example.test',
        ]);
        $currentRoom = ConferenceRoom::query()->create([
            'name' => 'Currently booked room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $futureRoom = ConferenceRoom::query()->create([
            'name' => 'Future booked room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);

        foreach ([
            [$currentRoom, '2026-09-04'],
            [$futureRoom, '2026-09-05'],
        ] as [$room, $bookingDate]) {
            ConferenceBooking::query()->create([
                'guest_id' => $guest->id,
                'conference_room_id' => $room->id,
                'booking_date' => $bookingDate,
                'start_time' => '10:00',
                'end_time' => '12:00',
                'attendees' => 10,
                'total_price' => 200,
                'status' => 'confirmed',
                'payment_status' => 'pending',
            ]);
        }

        $availability = (new OccupancyReport)->report()['conferenceAvailability'];

        self::assertSame(1, $availability['available']);
        self::assertSame(1, $availability['unavailable']);
    }

    public function test_future_reservation_does_not_make_a_restaurant_table_unavailable_now(): void
    {
        $this->travelTo('2026-09-04 11:00:00');

        $restaurant = Restaurant::query()->create([
            'name' => 'Occupancy restaurant',
            'description' => 'Restaurant fixture for occupancy reporting.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'OCC-T1',
            'capacity' => 4,
            'status' => 'reserved',
        ]);

        RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_name' => 'Future Guest',
            'guest_email' => 'future-table@example.test',
            'guest_phone' => '0240000011',
            'reservation_date' => '2026-09-05',
            'reservation_time' => '10:00',
            'duration_minutes' => 120,
            'number_of_guests' => 2,
            'status' => 'confirmed',
            'payment_status' => 'completed',
            'hold_status' => 'confirmed',
        ]);

        $tableStatus = (new OccupancyReport)->report()['tableStatus'];

        self::assertSame(1, $tableStatus['available']);
        self::assertSame(0, $tableStatus['reserved']);
    }

    public function test_only_active_current_reservations_block_restaurant_tables(): void
    {
        $this->travelTo('2026-09-04 11:00:00');

        $restaurant = Restaurant::query()->create([
            'name' => 'Current occupancy restaurant',
            'description' => 'Restaurant fixture for current table availability.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $activeTable = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'OCC-ACTIVE',
            'capacity' => 4,
            'status' => 'available',
        ]);
        $expiredTable = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'OCC-EXPIRED',
            'capacity' => 4,
            'status' => 'available',
        ]);

        foreach ([
            [$activeTable, 'confirmed', null],
            [$expiredTable, 'pending', now()->subMinute()],
        ] as [$table, $status, $holdUntil]) {
            RestaurantReservation::query()->create([
                'restaurant_id' => $restaurant->id,
                'restaurant_table_id' => $table->id,
                'guest_name' => 'Current Guest',
                'guest_email' => $table->table_number.'@example.test',
                'guest_phone' => '0240000012',
                'reservation_date' => '2026-09-04',
                'reservation_time' => '10:00',
                'duration_minutes' => 120,
                'number_of_guests' => 2,
                'status' => $status,
                'payment_status' => 'pending',
                'hold_status' => $status === 'pending' ? 'held' : 'confirmed',
                'hold_until' => $holdUntil,
            ]);
        }

        $tableStatus = (new OccupancyReport)->report()['tableStatus'];

        self::assertSame(1, $tableStatus['available']);
        self::assertSame(1, $tableStatus['reserved']);
        self::assertSame(0, $tableStatus['occupied']);
        self::assertSame(0, $tableStatus['unavailable']);
    }

    public function test_operational_table_states_remain_unavailable_without_schedule_conflicts(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Operational status restaurant',
            'description' => 'Restaurant fixture for table status reporting.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);

        foreach (['occupied', 'cleaning', 'maintenance'] as $status) {
            RestaurantTable::query()->create([
                'restaurant_id' => $restaurant->id,
                'table_number' => 'OCC-'.strtoupper($status),
                'capacity' => 4,
                'status' => $status,
            ]);
        }

        $tableStatus = (new OccupancyReport)->report()['tableStatus'];

        self::assertSame(0, $tableStatus['available']);
        self::assertSame(0, $tableStatus['reserved']);
        self::assertSame(1, $tableStatus['occupied']);
        self::assertSame(2, $tableStatus['unavailable']);
    }

    public function test_occupancy_report_counts_booking_nights_that_overlap_the_selected_period(): void
    {
        $this->travelTo('2026-09-15 12:00:00');

        $roomType = RoomType::query()->create([
            'name' => 'Report room',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);

        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'REPORT-1',
            'status' => 'available',
        ]);

        $guest = Guest::query()->create([
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
            'phone_number' => '0240000001',
            'email' => 'kojo@example.test',
        ]);

        Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => now()->startOfMonth()->addDay(),
            'check_out' => now()->startOfMonth()->addDays(4),
            'total_price' => 300,
            'status' => 'pending',
        ]);

        $report = (new OccupancyReport)->report();

        self::assertSame(1, $report['hotelBookings']);
        self::assertSame(3, $report['bookedRoomNights']);
        self::assertSame(now()->day, $report['roomNightCapacity']);
        self::assertEqualsWithDelta((3 / now()->day) * 100, $report['occupancyRate'], 0.001);
    }

    public function test_occupancy_clamps_stays_to_the_period_and_excludes_cancelled_records(): void
    {
        $this->travelTo('2026-08-15 12:00:00');
        [$guest, $room] = $this->occupancyBookingDependencies();

        Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-07-30',
            'check_out' => '2026-08-03',
            'total_price' => 400,
            'status' => 'pending',
        ]);
        Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-08',
            'total_price' => 300,
            'status' => 'cancelled',
        ]);

        $page = new OccupancyReport;
        $page->period = 'monthly';

        self::assertSame(2, $page->report()['bookedRoomNights']);
        self::assertSame(1, $page->report()['hotelBookings']);
    }

    public function test_current_maintenance_does_not_reduce_completed_historical_room_capacity(): void
    {
        $this->travelTo('2026-09-15 12:00:00');

        $roomType = RoomType::query()->create([
            'name' => 'Historical occupancy room',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'HISTORICAL-1',
            'status' => 'maintenance',
        ]);
        $guest = Guest::query()->create([
            'first_name' => 'Historical',
            'last_name' => 'Guest',
            'phone_number' => '0240000013',
            'email' => 'historical-occupancy@example.test',
        ]);

        Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-04',
            'total_price' => 300,
            'status' => 'confirmed',
        ]);

        $page = new OccupancyReport;
        $page->period = 'custom';
        $page->startDate = '2026-09-01';
        $page->endDate = '2026-09-03';
        $report = $page->report();

        self::assertSame(0, $report['roomsInService']);
        self::assertSame(3, $report['roomNightCapacity']);
        self::assertSame(3, $report['bookedRoomNights']);
        self::assertEqualsWithDelta(100, $report['occupancyRate'], 0.001);
    }

    public function test_current_maintenance_only_reduces_room_capacity_from_today_onward(): void
    {
        $this->travelTo('2026-09-03 12:00:00');

        $roomType = RoomType::query()->create([
            'name' => 'Mixed period room',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);

        foreach (['available', 'maintenance'] as $index => $status) {
            Room::query()->create([
                'room_type_id' => $roomType->id,
                'room_number' => 'MIXED-'.($index + 1),
                'status' => $status,
            ]);
        }

        $report = (new OccupancyReport)->report();

        self::assertSame(1, $report['roomsInService']);
        self::assertSame(5, $report['roomNightCapacity']);
    }

    public function test_occupancy_report_uses_a_memory_bounded_booking_iterator(): void
    {
        $source = file_get_contents(app_path('Filament/Admin/Pages/OccupancyReport.php'));

        self::assertMatchesRegularExpression('/lazyById|cursor/', $source);
        self::assertStringNotContainsString("->get(['id', 'check_in', 'check_out'])", $source);
    }

    public function test_occupancy_report_processes_a_large_booking_set(): void
    {
        $this->travelTo('2026-08-15 12:00:00');
        [$guest, $room] = $this->occupancyBookingDependencies();

        Booking::query()->insert(collect(range(1, 1100))->map(fn (int $index): array => [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-08-02',
            'check_out' => '2026-08-03',
            'total_price' => 100,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        $report = (new OccupancyReport)->report();

        self::assertSame(1100, $report['hotelBookings']);
        self::assertSame(1100, $report['bookedRoomNights']);
    }

    public function test_occupancy_page_exposes_loading_feedback_while_the_period_changes(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/pages/occupancy-report.blade.php'));

        self::assertStringContainsString('wire:loading', $view);
        self::assertStringContainsString('wire:target="applyReportPeriod,resetReportPeriod"', $view);
    }

    public function test_occupancy_stats_widget_uses_period_aware_overview_stats(): void
    {
        self::assertTrue(is_subclass_of(OccupancyStats::class, StatsOverviewWidget::class));

        $widget = new OccupancyStats;
        $widget->period = 'quarterly';
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertCount(5, $stats);
    }

    /**
     * @return array{0: Guest, 1: Room}
     */
    private function occupancyBookingDependencies(): array
    {
        $roomType = RoomType::query()->create([
            'name' => 'Occupancy test room',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'OCCUPANCY-'.Room::query()->count(),
            'status' => 'available',
        ]);
        $guest = Guest::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Guest',
            'phone_number' => '024'.str_pad((string) Guest::query()->count(), 7, '0', STR_PAD_LEFT),
            'email' => 'occupancy'.Guest::query()->count().'@example.test',
        ]);

        return [$guest, $room];
    }
}
