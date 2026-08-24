<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\OccupancyReport;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
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
        self::assertSame(31, $report['roomNightCapacity']);
        self::assertEqualsWithDelta(9.677, $report['occupancyRate'], 0.001);
    }
}
