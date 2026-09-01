<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\OccupancyReport;
use App\Filament\Admin\Widgets\OccupancyStats;
use App\Models\Booking;
use App\Models\Guest;
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
        $reportPage->period = 'this_quarter';

        self::assertSame('This quarter', $reportPage->periodLabel());

        $report = $reportPage->report();

        self::assertArrayHasKey('occupancyRate', $report);
        self::assertArrayHasKey('bookedRoomNights', $report);
        self::assertArrayHasKey('conferenceAvailability', $report);
        self::assertArrayHasKey('tableStatus', $report);
    }

    public function test_occupancy_report_counts_booking_nights_that_overlap_the_selected_period(): void
    {
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
        self::assertSame(now()->daysInMonth, $report['roomNightCapacity']);
        self::assertEqualsWithDelta((3 / now()->daysInMonth) * 100, $report['occupancyRate'], 0.001);
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
        $page->period = 'this_month';

        self::assertSame(2, $page->report()['bookedRoomNights']);
        self::assertSame(1, $page->report()['hotelBookings']);
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
        self::assertStringContainsString('wire:target="period"', $view);
    }

    public function test_occupancy_stats_widget_uses_period_aware_overview_stats(): void
    {
        self::assertTrue(is_subclass_of(OccupancyStats::class, StatsOverviewWidget::class));

        $widget = new OccupancyStats;
        $widget->period = 'this_quarter';
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
