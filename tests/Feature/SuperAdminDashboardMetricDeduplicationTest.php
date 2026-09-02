<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\SuperAdminFinanceStats;
use App\Filament\Admin\Widgets\SuperAdminStats;
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

class SuperAdminDashboardMetricDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_overviews_use_unique_decision_metrics(): void
    {
        $executiveStats = $this->statsFor(new SuperAdminStats);
        $financeStats = $this->statsFor(new SuperAdminFinanceStats);
        $executiveLabels = array_keys($executiveStats);
        $financeLabels = array_keys($financeStats);

        self::assertContains('Cancellation Rate', $executiveLabels);
        self::assertNotContains('All Reservations', $executiveLabels);
        self::assertContains('Total Receivables', $financeLabels);
        self::assertNotContains('Paid Revenue', $financeLabels);
        self::assertSame(
            count(array_merge($executiveLabels, $financeLabels)),
            count(array_unique(array_merge($executiveLabels, $financeLabels))),
        );
    }

    public function test_cancellation_rate_only_uses_reservations_created_in_the_selected_period(): void
    {
        [$guest, $room] = $this->hotelFixture();

        $this->createBooking($guest, $room, 'confirmed', '2026-08-05 10:00:00');
        $this->createBooking($guest, $room, 'cancelled', '2026-08-06 10:00:00');
        $this->createBooking($guest, $room, 'cancelled', '2026-07-31 10:00:00');

        $stats = $this->statsFor(new SuperAdminStats, '2026-08-01', '2026-08-31');

        self::assertSame('50.0%', $stats['Cancellation Rate']->getValue());
        self::assertSame('1 of 2 reservations cancelled', $stats['Cancellation Rate']->getDescription());
    }

    public function test_total_receivables_include_each_unpaid_channel_and_exclude_cancelled_or_out_of_range_records(): void
    {
        [$guest, $room] = $this->hotelFixture();
        [$conferenceRoom, $restaurant, $table] = $this->serviceFixture();

        $this->createBooking($guest, $room, 'confirmed', '2026-08-05 10:00:00', 100);
        $this->createConferenceBooking($guest, $conferenceRoom, 'confirmed', '2026-08-06 10:00:00', 200);
        $this->createTableReservation($guest, $restaurant, $table, 'confirmed', '2026-08-07 10:00:00', 50);
        $this->createRestaurantOrder($guest, 'confirmed', '2026-08-08 10:00:00', 25);

        $this->createBooking($guest, $room, 'cancelled', '2026-08-09 10:00:00', 500);
        $this->createRestaurantOrder($guest, 'confirmed', '2026-07-31 10:00:00', 900);

        $stats = $this->statsFor(new SuperAdminFinanceStats, '2026-08-01', '2026-08-31');

        self::assertSame('GHS 375.00', $stats['Total Receivables']->getValue());
        self::assertSame('All valid unpaid transactions', $stats['Total Receivables']->getDescription());
        self::assertSame('Corporate subset of total receivables', $stats['Corporate Outstanding']->getDescription());
    }

    /**
     * @return array<string, Stat>
     */
    private function statsFor(object $widget, ?string $startDate = null, ?string $endDate = null): array
    {
        $widget->pageFilters = array_filter([
            'period' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    /**
     * @return array{0: Guest, 1: Room}
     */
    private function hotelFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Dashboard',
            'last_name' => 'Guest',
            'email' => 'dashboard-metrics@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Dashboard Metrics Room',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'METRIC-1',
            'status' => 'available',
        ]);

        return [$guest, $room];
    }

    /**
     * @return array{0: ConferenceRoom, 1: Restaurant, 2: RestaurantTable}
     */
    private function serviceFixture(): array
    {
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Dashboard Metrics Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Dashboard Metrics Restaurant',
            'description' => 'Restaurant fixture for super-admin dashboard metrics.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'METRIC-T1',
            'capacity' => 4,
        ]);

        return [$conferenceRoom, $restaurant, $table];
    }

    private function createBooking(Guest $guest, Room $room, string $status, string $createdAt, float $total = 100): Booking
    {
        $booking = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-11',
            'total_price' => $total,
            'status' => $status,
            'payment_status' => 'pending',
        ]);

        return $this->createdAt($booking, $createdAt);
    }

    private function createConferenceBooking(Guest $guest, ConferenceRoom $room, string $status, string $createdAt, float $total): ConferenceBooking
    {
        $booking = ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $room->id,
            'booking_date' => '2026-09-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => $total,
            'status' => $status,
            'payment_status' => 'pending',
        ]);

        return $this->createdAt($booking, $createdAt);
    }

    private function createTableReservation(Guest $guest, Restaurant $restaurant, RestaurantTable $table, string $status, string $createdAt, float $total): RestaurantReservation
    {
        $reservation = RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-09-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => $total,
            'status' => $status,
            'payment_status' => 'pending',
        ]);

        return $this->createdAt($reservation, $createdAt);
    }

    private function createRestaurantOrder(Guest $guest, string $status, string $createdAt, float $total): RestaurantOrder
    {
        $order = RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'METRIC-'.str()->random(12),
            'customer_email' => $guest->email,
            'payment_status' => 'pending',
            'status' => $status,
            'total' => $total,
            'ordering_channel' => 'web',
        ]);

        return $this->createdAt($order, $createdAt);
    }

    private function createdAt(Model $model, string $createdAt): Model
    {
        $model->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();

        return $model;
    }
}
