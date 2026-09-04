<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\OccupancyReport;
use App\Filament\Admin\Widgets\OccupancyStats;
use App\Filament\Admin\Widgets\OccupancyTrendChart;
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
use App\Services\CurrentVenueAvailability;
use Carbon\CarbonInterface;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OccupancyReportTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('authorizedOccupancyDepartments')]
    public function test_authorized_staff_can_open_the_occupancy_report(string $department): void
    {
        $user = User::factory()->create(['department' => $department]);

        $this->actingAs($user)
            ->get('/admin/occupancy-report')
            ->assertOk();
    }

    #[DataProvider('unauthorizedOccupancyDepartments')]
    public function test_other_authenticated_roles_cannot_open_the_occupancy_report(string $department): void
    {
        $user = User::factory()->create(['department' => $department]);

        $this->actingAs($user)
            ->get('/admin/occupancy-report')
            ->assertForbidden();
    }

    public function test_anonymous_users_are_redirected_to_the_admin_login(): void
    {
        $this->get('/admin/occupancy-report')
            ->assertRedirect('/admin/login');
    }

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

    public function test_live_availability_uses_one_timestamp_for_the_complete_snapshot(): void
    {
        $availability = new class extends CurrentVenueAvailability
        {
            /** @var list<CarbonInterface> */
            public array $observedAt = [];

            public function conferenceRooms(CarbonInterface $at): array
            {
                $this->observedAt[] = $at;

                return ['available' => 0, 'unavailable' => 0];
            }

            public function restaurantTables(CarbonInterface $at): array
            {
                $this->observedAt[] = $at;

                return ['available' => 0, 'reserved' => 0, 'occupied' => 0, 'unavailable' => 0];
            }
        };
        $this->app->instance(CurrentVenueAvailability::class, $availability);
        $this->travelTo('2026-09-04 12:34:56');

        $report = (new OccupancyReport)->report();

        self::assertArrayHasKey('snapshotAt', $report);
        self::assertSame($report['snapshotAt'], $availability->observedAt[0]);
        self::assertSame($report['snapshotAt'], $availability->observedAt[1]);
    }

    public function test_occupancy_rate_is_undefined_when_selected_period_has_no_capacity(): void
    {
        $report = (new OccupancyReport)->report();

        self::assertSame(0, $report['roomNightCapacity']);
        self::assertNull($report['occupancyRate']);
    }

    public function test_available_capacity_without_bookings_remains_zero_percent_occupancy(): void
    {
        $roomType = RoomType::query()->create([
            'name' => 'Empty occupancy room',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'EMPTY-1',
            'status' => 'available',
        ]);

        $report = (new OccupancyReport)->report();

        self::assertGreaterThan(0, $report['roomNightCapacity']);
        self::assertEqualsWithDelta(0, $report['occupancyRate'], 0.001);

        $widget = new OccupancyStats;
        $widget->summary = $this->statsSummary($report);
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);
        $occupancy = collect($method->invoke($widget))
            ->first(fn ($stat): bool => $stat->getLabel() === 'Room occupancy');

        self::assertSame('0.0%', $occupancy->getValue());
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

    public function test_occupancy_trend_splits_a_stay_across_daily_buckets_and_preserves_empty_dates(): void
    {
        $this->travelTo('2026-09-04 12:00:00');
        [$guest, $room] = $this->occupancyBookingDependencies();

        Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-02',
            'check_out' => '2026-09-04',
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $page = new OccupancyReport;
        $page->period = 'custom';
        $page->startDate = '2026-09-01';
        $page->endDate = '2026-09-04';

        $report = $page->report();
        $trend = $report['occupancyTrend'];

        self::assertSame('day', $trend['granularity']);
        self::assertSame(['Sep 1', 'Sep 2', 'Sep 3', 'Sep 4'], $trend['labels']);
        self::assertSame([0, 1, 1, 0], $trend['bookedRoomNights']);
        self::assertSame([1, 1, 1, 1], $trend['roomNightCapacities']);
        self::assertSame([0.0, 100.0, 100.0, 0.0], $trend['occupancyRates']);
        self::assertSame($report['bookedRoomNights'], array_sum($trend['bookedRoomNights']));
        self::assertSame($report['roomNightCapacity'], array_sum($trend['roomNightCapacities']));
    }

    public function test_occupancy_trend_uses_readable_buckets_for_longer_ranges(): void
    {
        $this->travelTo('2026-09-30 12:00:00');

        $quarterlyPage = new OccupancyReport;
        $quarterlyPage->period = 'quarterly';
        $quarterlyTrend = $quarterlyPage->report()['occupancyTrend'];

        self::assertSame('week', $quarterlyTrend['granularity']);
        self::assertCount(14, $quarterlyTrend['labels']);

        $yearlyPage = new OccupancyReport;
        $yearlyPage->period = 'yearly';
        $yearlyTrend = $yearlyPage->report()['occupancyTrend'];

        self::assertSame('month', $yearlyTrend['granularity']);
        self::assertSame(['Jan 2026', 'Feb 2026', 'Mar 2026', 'Apr 2026', 'May 2026', 'Jun 2026', 'Jul 2026', 'Aug 2026', 'Sep 2026'], $yearlyTrend['labels']);

        $longRangePage = new OccupancyReport;
        $longRangePage->period = 'custom';
        $longRangePage->startDate = '2023-01-01';
        $longRangePage->endDate = '2026-09-30';
        $longRangeTrend = $longRangePage->report()['occupancyTrend'];

        self::assertSame('year', $longRangeTrend['granularity']);
        self::assertCount(4, $longRangeTrend['labels']);
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

    public function test_occupancy_report_date_filters_do_not_wrap_indexed_columns_in_sql_functions(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        (new OccupancyReport)->report();

        $bookingQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains($query, 'from "bookings"')
                && str_contains($query, 'check_in'));

        DB::disableQueryLog();

        self::assertNotEmpty($bookingQueries);

        foreach ($bookingQueries as $query) {
            self::assertDoesNotMatchRegularExpression('/\b(?:date|strftime)\s*\(/i', $query);
        }
    }

    public function test_occupancy_report_date_indexes_are_installed(): void
    {
        foreach ($this->occupancyIndexNames() as $table => $indexes) {
            foreach ($indexes as $index) {
                self::assertContains($index, Schema::getIndexListing($table));
            }
        }
    }

    public function test_occupancy_report_date_index_migration_is_reversible(): void
    {
        $path = database_path('migrations/2026_09_04_000200_add_occupancy_report_indexes.php');

        self::assertFileExists($path);

        $migration = require $path;
        $migration->down();

        foreach ($this->occupancyIndexNames() as $table => $indexes) {
            foreach ($indexes as $index) {
                self::assertNotContains($index, Schema::getIndexListing($table));
            }
        }

        $migration->up();

        foreach ($this->occupancyIndexNames() as $table => $indexes) {
            foreach ($indexes as $index) {
                self::assertContains($index, Schema::getIndexListing($table));
            }
        }
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

        DB::flushQueryLog();
        DB::enableQueryLog();
        $report = (new OccupancyReport)->report();
        $bookingChunks = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains($query, 'from "bookings"')
                && preg_match('/order by (?:"bookings"\.)?"id" asc limit 1000/i', $query) === 1)
            ->values();
        DB::disableQueryLog();

        self::assertSame(1100, $report['hotelBookings']);
        self::assertSame(1100, $report['bookedRoomNights']);
        self::assertCount(2, $bookingChunks);
        self::assertDoesNotMatchRegularExpression('/\boffset\b/i', $bookingChunks->first());
        self::assertMatchesRegularExpression(
            '/(?:"bookings"\.)?"id" > \?/i',
            $bookingChunks->last(),
        );
    }

    public function test_occupancy_page_marks_only_selected_period_results_as_busy_while_the_period_changes(): void
    {
        $user = User::factory()->create(['department' => 'management']);

        $response = $this->actingAs($user)->get('/admin/occupancy-report');

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $selectedPeriod = $xpath->query('//section[@aria-labelledby="selected-period-heading"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $selectedPeriod);
        self::assertSame('aria-busy', $selectedPeriod->getAttribute('wire:loading.attr'));
        self::assertSame('applyReportPeriod,resetReportPeriod', $selectedPeriod->getAttribute('wire:target'));

        $results = $xpath->query('./div[@data-occupancy-period-results]', $selectedPeriod)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $results);
        self::assertStringContainsString('pointer-events-none', $results->getAttribute('wire:loading.class'));
        self::assertStringContainsString('opacity-60', $results->getAttribute('wire:loading.class'));

        $status = $xpath->query('./div[@role="status"]', $selectedPeriod)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $status);
        self::assertSame('polite', $status->getAttribute('aria-live'));
        self::assertSame('true', $status->getAttribute('aria-atomic'));
        self::assertSame('applyReportPeriod,resetReportPeriod', $status->getAttribute('wire:target'));
        self::assertTrue($status->hasAttribute('wire:loading.flex'));
        self::assertStringContainsString('Updating occupancy report', $status->textContent);

        $liveSnapshot = $xpath->query('//section[@aria-labelledby="live-snapshot-heading"]')?->item(0);
        self::assertInstanceOf(\DOMElement::class, $liveSnapshot);
        self::assertFalse($liveSnapshot->hasAttribute('wire:loading.attr'));
        self::assertSame(0, $xpath->query('.//*[@role="status"]', $liveSnapshot)?->count());
    }

    public function test_occupancy_period_actions_are_disabled_during_a_report_refresh(): void
    {
        $user = User::factory()->create(['department' => 'management']);

        $response = $this->actingAs($user)->get('/admin/occupancy-report');

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $buttons = $xpath->query('//form[@*[name()="wire:submit"]="applyReportPeriod"]//button');

        self::assertNotFalse($buttons);
        self::assertCount(2, $buttons);

        foreach ($buttons as $button) {
            self::assertSame('disabled', $button->getAttribute('wire:loading.attr'));
            self::assertSame('applyReportPeriod,resetReportPeriod', $button->getAttribute('wire:target'));
        }
    }

    public function test_occupancy_page_only_allows_date_edits_for_custom_ranges(): void
    {
        $user = User::factory()->create(['department' => 'management']);

        $presetResponse = $this->actingAs($user)->get('/admin/occupancy-report');
        $presetResponse->assertOk()->assertSee('Dates are calculated automatically for preset periods.');

        foreach ($this->reportDateInputs($presetResponse->getContent()) as $input) {
            self::assertTrue($input->hasAttribute('disabled'));
        }

        $customResponse = $this->actingAs($user)->get('/admin/occupancy-report?period=custom&startDate=2026-09-01&endDate=2026-09-04');
        $customResponse->assertOk()->assertSee('Choose the exact start and end dates for this report.');

        foreach ($this->reportDateInputs($customResponse->getContent()) as $input) {
            self::assertFalse($input->hasAttribute('disabled'));
        }
    }

    public function test_occupancy_page_separates_selected_period_results_from_the_live_snapshot(): void
    {
        $this->travelTo('2026-09-04 12:34:56');
        $user = User::factory()->create(['department' => 'management']);

        $response = $this->actingAs($user)->get('/admin/occupancy-report');

        $response->assertOk()->assertSeeInOrder([
            'Selected period performance',
            'Live operational snapshot',
        ]);

        $selectedPeriod = $this->reportSection($response->getContent(), 'selected-period-heading');
        self::assertStringContainsString('Room occupancy', $selectedPeriod);
        self::assertStringContainsString('Scheduled use', $selectedPeriod);
        self::assertStringNotContainsString('available now', strtolower($selectedPeriod));

        $liveSnapshot = $this->reportSection($response->getContent(), 'live-snapshot-heading');
        self::assertStringContainsString('As of Sep 4, 2026 12:34 PM', $liveSnapshot);
        self::assertStringContainsString('Room inventory', $liveSnapshot);
        self::assertStringContainsString('Venue availability', $liveSnapshot);
        self::assertStringContainsString('Tables available now', $liveSnapshot);
        self::assertStringNotContainsString('Room occupancy', $liveSnapshot);
    }

    public function test_occupancy_stats_widget_uses_period_aware_overview_stats(): void
    {
        self::assertTrue(is_subclass_of(OccupancyStats::class, StatsOverviewWidget::class));

        $widget = new OccupancyStats;
        $widget->summary['occupancyRate'] = 25;
        $widget->periodLabel = 'Quarterly';
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertSame([
            'Room occupancy',
            'Booked room nights',
            'Room-night capacity',
        ], array_map(fn ($stat): string => $stat->getLabel(), $stats));
        self::assertSame('Quarterly', $stats[0]->getDescription());
    }

    public function test_occupancy_trend_widget_presents_rates_nights_and_capacity_without_requerying(): void
    {
        self::assertTrue(is_subclass_of(OccupancyTrendChart::class, ChartWidget::class));

        $widget = new OccupancyTrendChart;
        $widget->trend = [
            'granularity' => 'day',
            'labels' => ['Sep 1', 'Sep 2'],
            'bookedRoomNights' => [0, 1],
            'roomNightCapacities' => [2, 2],
            'occupancyRates' => [0.0, 50.0],
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();
        $dataMethod = new \ReflectionMethod($widget, 'getData');
        $dataMethod->setAccessible(true);
        $data = $dataMethod->invoke($widget);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        self::assertCount(0, $queries);
        self::assertSame(['Sep 1', 'Sep 2'], $data['labels']);
        self::assertSame(
            ['Occupancy rate', 'Booked room nights', 'Room-night capacity'],
            array_column($data['datasets'], 'label'),
        );
        self::assertSame('line', $data['datasets'][0]['type']);
        self::assertSame('yPercentage', $data['datasets'][0]['yAxisID']);
        self::assertSame('yNights', $data['datasets'][1]['yAxisID']);
        self::assertSame('yNights', $data['datasets'][2]['yAxisID']);

        $optionsMethod = new \ReflectionMethod($widget, 'getOptions');
        $optionsMethod->setAccessible(true);
        $options = $optionsMethod->invoke($widget);

        self::assertSame(100, $options['scales']['yPercentage']['suggestedMax']);
        self::assertSame(12, $options['scales']['x']['ticks']['maxTicksLimit']);
        self::assertSame('bottom', $options['plugins']['legend']['position']);
    }

    public function test_occupancy_page_places_the_trend_between_summary_metrics_and_scheduled_use(): void
    {
        $user = User::factory()->create(['department' => 'management']);

        $response = $this->actingAs($user)->get('/admin/occupancy-report');

        $response->assertOk()->assertSeeInOrder([
            'Room occupancy',
            'Occupancy trend',
            'Scheduled use',
        ]);
    }

    public function test_occupancy_trend_empty_state_depends_on_room_night_capacity(): void
    {
        $emptyWidget = new OccupancyTrendChart;
        $emptyWidget->trend['roomNightCapacities'] = [0, 0];

        self::assertTrue($emptyWidget->isEmpty());

        $availableWidget = new OccupancyTrendChart;
        $availableWidget->trend['roomNightCapacities'] = [0, 1];

        self::assertFalse($availableWidget->isEmpty());
    }

    public function test_occupancy_trend_visibility_matches_the_report_roles(): void
    {
        foreach (['super_admin', 'admin', 'manager', 'receptionist'] as $roleName) {
            $user = User::factory()->create();
            $user->assignRole(Role::findOrCreate($roleName, 'web'));

            $this->actingAs($user);

            self::assertTrue(OccupancyTrendChart::canView(), "Expected [{$roleName}] to see the occupancy trend.");
        }

        $accountant = User::factory()->create();
        $accountant->assignRole(Role::findOrCreate('accountant', 'web'));

        $this->actingAs($accountant);

        self::assertFalse(OccupancyTrendChart::canView());
    }

    public function test_occupancy_stats_widget_stacks_cards_before_using_three_desktop_columns(): void
    {
        $widget = new OccupancyStats;
        $method = new \ReflectionMethod($widget, 'getColumns');
        $method->setAccessible(true);

        self::assertSame([
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ], $method->invoke($widget));
    }

    public function test_occupancy_stat_cards_use_distinct_colors_and_readable_numbers(): void
    {
        $widget = new OccupancyStats;
        $widget->summary = [
            'occupancyRate' => 64.5,
            'bookedRoomNights' => 1234,
            'roomNightCapacity' => 2345,
        ];
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertSame(['primary', 'success', 'info'], array_map(
            fn ($stat): string|array|null => $stat->getColor(),
            $stats,
        ));

        foreach ($stats as $stat) {
            self::assertStringContainsString('tabular-nums', $stat->getExtraAttributes()['class'] ?? '');
        }
    }

    public function test_scheduled_use_metrics_render_as_three_distinct_responsive_cards(): void
    {
        $this->travelTo('2026-09-04 12:00:00');
        [$guest, $room] = $this->occupancyBookingDependencies();
        Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-03',
            'check_out' => '2026-09-05',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $user = User::factory()->create(['department' => 'management']);

        $response = $this->actingAs($user)->get('/admin/occupancy-report');

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $grid = $xpath->query('//ul[@aria-label="Scheduled use metrics"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $grid);
        self::assertStringContainsString('md:grid-cols-2', $grid->getAttribute('class'));
        self::assertStringContainsString('xl:grid-cols-3', $grid->getAttribute('class'));

        $cards = $xpath->query('./li', $grid);
        self::assertNotFalse($cards);
        self::assertCount(3, $cards);

        foreach (['primary', 'info', 'warning'] as $index => $tone) {
            self::assertStringContainsString("border-{$tone}-200", $cards->item($index)->getAttribute('class'));
        }

        $response->assertDontSee('No scheduled occupancy activity');
    }

    public function test_period_without_scheduled_activity_renders_guidance_instead_of_zero_cards(): void
    {
        $this->travelTo('2026-09-04 12:00:00');
        $this->occupancyBookingDependencies();
        $user = User::factory()->create(['department' => 'management']);

        $response = $this->actingAs($user)->get(
            '/admin/occupancy-report?period=custom&startDate=2026-09-01&endDate=2026-09-04',
        );

        $response->assertOk();
        $response->assertSee('No scheduled occupancy activity');
        $response->assertSee('Room occupancy');
        $response->assertSee('Live operational snapshot');

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        self::assertSame(0, $xpath->query('//ul[@aria-label="Scheduled use metrics"]')?->count());

        $link = $xpath->query('//a[@aria-label="Open the booking calendar for the selected period"]')?->item(0);
        self::assertInstanceOf(\DOMElement::class, $link);
        $url = $link->getAttribute('href');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('/admin/booking-calendar', parse_url($url, PHP_URL_PATH));
        self::assertSame('all', $query['type'] ?? null);
        self::assertSame('reportable', $query['status_scope'] ?? null);
        self::assertSame('2026-09-01', $query['start_date'] ?? null);
        self::assertSame('2026-09-04', $query['end_date'] ?? null);
    }

    public function test_occupancy_activity_stats_link_to_the_matching_hotel_calendar_scope(): void
    {
        $widget = new OccupancyStats;
        $widget->hotelBookingsUrl = 'http://localhost/admin/booking-calendar?type=hotel&status_scope=reportable&start_date=2026-09-01&end_date=2026-09-04';
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertSame($widget->hotelBookingsUrl, $stats[0]->getUrl());
        self::assertSame($widget->hotelBookingsUrl, $stats[1]->getUrl());
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stats[0]->getDescriptionIcon());
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stats[1]->getDescriptionIcon());
        self::assertNull($stats[2]->getUrl());
    }

    public function test_scheduled_use_cards_link_to_matching_calendar_types_and_selected_dates(): void
    {
        [$guest, $room] = $this->occupancyBookingDependencies();
        Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-02',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $user = User::factory()->create(['department' => 'management']);

        $response = $this->actingAs($user)->get(
            '/admin/occupancy-report?period=custom&startDate=2026-09-01&endDate=2026-09-04',
        );

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        foreach ([
            'View hotel stays for the selected period' => 'hotel',
            'View conference bookings for the selected period' => 'conference',
            'View table reservations for the selected period' => 'restaurant',
        ] as $label => $type) {
            $link = $xpath->query('//a[@aria-label="'.$label.'"]')?->item(0);

            self::assertInstanceOf(\DOMElement::class, $link);
            $url = $link->getAttribute('href');
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            self::assertSame('/admin/booking-calendar', parse_url($url, PHP_URL_PATH));
            self::assertSame($type, $query['type'] ?? null);
            self::assertSame('reportable', $query['status_scope'] ?? null);
            self::assertSame('2026-09-01', $query['start_date'] ?? null);
            self::assertSame('2026-09-04', $query['end_date'] ?? null);
        }
    }

    public function test_occupancy_stats_widget_builds_cards_without_requerying_report_data(): void
    {
        $widget = new OccupancyStats;
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $method->invoke($widget);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        self::assertCount(0, $queries);
    }

    public function test_occupancy_stats_widget_labels_zero_capacity_as_not_available(): void
    {
        $widget = new OccupancyStats;
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $occupancy = collect($method->invoke($widget))
            ->first(fn ($stat): bool => $stat->getLabel() === 'Room occupancy');

        self::assertSame('N/A', $occupancy->getValue());
        self::assertSame('No room-night capacity in Monthly', $occupancy->getDescription());
        self::assertSame('gray', $occupancy->getColor());
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

    /**
     * @param  array<string, mixed>  $report
     * @return array{occupancyRate: float|null, bookedRoomNights: int, roomNightCapacity: int}
     */
    private function statsSummary(array $report): array
    {
        return [
            'occupancyRate' => $report['occupancyRate'],
            'bookedRoomNights' => $report['bookedRoomNights'],
            'roomNightCapacity' => $report['roomNightCapacity'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function occupancyIndexNames(): array
    {
        return [
            'bookings' => [
                'bookings_check_in_status_index',
                'bookings_check_out_status_index',
            ],
            'conference_bookings' => [
                'conference_bookings_booking_date_status_index',
            ],
            'restaurant_reservations' => [
                'restaurant_reservations_reservation_date_status_index',
            ],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authorizedOccupancyDepartments(): array
    {
        return [
            'super admin' => ['super_admin'],
            'admin' => ['admin'],
            'manager' => ['management'],
            'receptionist' => ['reception'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unauthorizedOccupancyDepartments(): array
    {
        return [
            'accountant' => ['accounting'],
            'housekeeping' => ['housekeeping'],
            'kitchen manager' => ['kitchen_manager'],
            'kitchen staff' => ['kitchen_staff'],
            'guest' => ['guest'],
        ];
    }

    /**
     * @return list<\DOMElement>
     */
    private function reportDateInputs(string $html): array
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $inputs = $xpath->query('//input[@type="date" and (@*[name()="wire:model"]="draftStartDate" or @*[name()="wire:model"]="draftEndDate")]');

        self::assertNotFalse($inputs);
        self::assertCount(2, $inputs);

        return iterator_to_array($inputs);
    }

    private function reportSection(string $html, string $labelledBy): string
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $section = $xpath->query("//section[@aria-labelledby='{$labelledBy}']")?->item(0);

        self::assertInstanceOf(\DOMElement::class, $section);

        return trim($section->textContent);
    }
}
