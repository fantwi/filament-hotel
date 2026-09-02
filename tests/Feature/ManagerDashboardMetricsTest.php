<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\ManagerOperationsChart;
use App\Filament\Admin\Widgets\ManagerOperationsStats;
use App\Filament\Admin\Widgets\ManagerStats;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\Guest;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ManagerDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_activity_stats_exclude_cancelled_expired_and_no_show_records(): void
    {
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();

        foreach (['confirmed', 'cancelled', 'expired', 'no_show'] as $status) {
            $this->booking($guest, $room, '2026-08-10', '2026-08-11', $status);
        }

        foreach (['confirmed', 'cancelled'] as $status) {
            $this->conferenceBooking($guest, $conferenceRoom, '2026-08-10', $status);
        }

        foreach (['confirmed', 'cancelled', 'no_show'] as $status) {
            $this->tableReservation($guest, $restaurant, $table, '2026-08-10', $status);
        }

        foreach (['confirmed', 'cancelled'] as $status) {
            $this->restaurantOrder($guest, '2026-08-10 09:00:00', $status);
        }

        $stats = $this->statsFor(ManagerStats::class, '2026-08-10', '2026-08-12');

        self::assertSame(1, $stats['Hotel Arrivals']->getValue());
        self::assertSame(1, $stats['Conference Events']->getValue());
        self::assertSame(1, $stats['Restaurant Reservations']->getValue());
        self::assertSame(1, $stats['Food Orders']->getValue());

        $operations = $this->statsFor(ManagerOperationsStats::class, '2026-08-10', '2026-08-12');

        self::assertSame('1', $operations['Active kitchen orders']->getValue());
    }

    public function test_active_stays_use_stay_overlap_instead_of_booking_creation_date(): void
    {
        [$guest, $room] = $this->serviceFixture();

        $this->booking($guest, $room, '2026-08-09', '2026-08-11', 'confirmed', '2026-07-01 09:00:00');
        $this->booking($guest, $room, '2026-08-12', '2026-08-14', 'checked_in', '2026-07-02 09:00:00');
        $this->booking($guest, $room, '2026-08-09', '2026-08-11', 'cancelled', '2026-07-03 09:00:00');
        $this->booking($guest, $room, '2026-08-07', '2026-08-10', 'confirmed', '2026-08-01 09:00:00');
        $this->booking($guest, $room, '2026-08-13', '2026-08-14', 'confirmed', '2026-08-02 09:00:00');

        $stats = $this->statsFor(ManagerOperationsStats::class, '2026-08-10', '2026-08-12');

        self::assertSame('2', $stats['Active stays']->getValue());
    }

    public function test_operations_chart_uses_service_dates_and_excludes_invalid_records(): void
    {
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();

        $this->booking($guest, $room, '2026-08-04', '2026-08-05', 'confirmed', '2026-07-01 09:00:00');
        $this->booking($guest, $room, '2026-08-04', '2026-08-05', 'cancelled', '2026-07-01 10:00:00');
        $this->conferenceBooking($guest, $conferenceRoom, '2026-08-05', 'confirmed', '2026-07-01 09:00:00');
        $this->conferenceBooking($guest, $conferenceRoom, '2026-08-05', 'cancelled', '2026-07-01 10:00:00');
        $this->tableReservation($guest, $restaurant, $table, '2026-08-06', 'confirmed', '2026-07-01 09:00:00');
        $this->tableReservation($guest, $restaurant, $table, '2026-08-06', 'no_show', '2026-07-01 10:00:00');
        $this->restaurantOrder($guest, '2026-08-07 09:00:00', 'confirmed');
        $this->restaurantOrder($guest, '2026-08-07 10:00:00', 'cancelled');

        $data = $this->chartData('weekly', '2026-08-03', '2026-08-09');

        self::assertSame([0, 1, 0, 0, 0, 0, 0], $data['datasets'][0]['data']);
        self::assertSame([0, 0, 1, 0, 0, 0, 0], $data['datasets'][1]['data']);
        self::assertSame([0, 0, 0, 1, 0, 0, 0], $data['datasets'][2]['data']);
        self::assertSame([0, 0, 0, 0, 1, 0, 0], $data['datasets'][3]['data']);
    }

    public function test_long_daily_date_ranges_use_yearly_buckets(): void
    {
        $data = $this->chartData('daily', '2020-01-01', '2026-12-31');

        self::assertCount(7, $data['labels']);
        self::assertSame('2020', $data['labels'][0]);
        self::assertSame('2026', $data['labels'][6]);

        foreach ($data['datasets'] as $dataset) {
            self::assertCount(7, $dataset['data']);
        }
    }

    /**
     * @return array{Guest, Room, ConferenceRoom, Restaurant, RestaurantTable}
     */
    private function serviceFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Manager',
            'last_name' => 'Metrics',
            'email' => 'manager-metrics@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Manager Metrics Room',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'MANAGER-METRIC-1',
            'status' => 'available',
        ]);
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Manager Metrics Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Manager Metrics Restaurant',
            'description' => 'Restaurant fixture for manager dashboard metrics.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'MANAGER-METRIC-T1',
            'capacity' => 4,
        ]);

        return [$guest, $room, $conferenceRoom, $restaurant, $table];
    }

    private function booking(
        Guest $guest,
        Room $room,
        string $checkIn,
        string $checkOut,
        string $status,
        string $createdAt = '2026-08-10 08:00:00',
    ): void {
        $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_price' => 100,
            'status' => $status,
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function conferenceBooking(
        Guest $guest,
        ConferenceRoom $room,
        string $bookingDate,
        string $status,
        string $createdAt = '2026-08-10 08:00:00',
    ): void {
        $this->createdAt(ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $room->id,
            'booking_date' => $bookingDate,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => $status,
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function tableReservation(
        Guest $guest,
        Restaurant $restaurant,
        RestaurantTable $table,
        string $reservationDate,
        string $status,
        string $createdAt = '2026-08-10 08:00:00',
    ): void {
        $this->createdAt(RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => $reservationDate,
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 50,
            'status' => $status,
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function restaurantOrder(Guest $guest, string $createdAt, string $status): void
    {
        $this->createdAt(RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'MANAGER-METRIC-'.str()->upper(str()->random(12)),
            'subtotal' => 25,
            'total' => 25,
            'status' => $status,
            'payment_status' => 'completed',
            'ordering_channel' => 'web',
        ]), $createdAt);
    }

    /**
     * @param  class-string  $widgetClass
     * @return array<string, Stat>
     */
    private function statsFor(string $widgetClass, string $startDate, string $endDate): array
    {
        $widget = new $widgetClass;
        $widget->pageFilters = [
            'period' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    private function chartData(string $period, string $startDate, string $endDate): array
    {
        $widget = new ManagerOperationsChart;
        $widget->pageFilters = [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $method = new ReflectionMethod($widget, 'getData');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }

    private function createdAt(Model $model, string $createdAt): void
    {
        $model->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();
    }
}
