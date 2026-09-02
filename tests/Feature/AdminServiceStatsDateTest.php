<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\AdminServiceStats;
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

class AdminServiceStatsDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_service_stats_use_service_dates_instead_of_record_creation_dates(): void
    {
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();

        $this->booking($guest, $room, '2026-08-09', '2026-08-11', '2026-07-01 09:00:00');
        $this->booking($guest, $room, '2026-08-12', '2026-08-13', '2026-07-02 09:00:00');
        $this->booking($guest, $room, '2026-08-13', '2026-08-14', '2026-08-11 09:00:00');

        $this->conferenceBooking($guest, $conferenceRoom, '2026-08-10', '2026-07-01 09:00:00');
        $this->conferenceBooking($guest, $conferenceRoom, '2026-08-12', '2026-07-02 09:00:00');
        $this->conferenceBooking($guest, $conferenceRoom, '2026-08-13', '2026-08-11 09:00:00');

        $this->tableReservation($guest, $restaurant, $table, '2026-08-10', '2026-07-01 09:00:00');
        $this->tableReservation($guest, $restaurant, $table, '2026-08-12', '2026-07-02 09:00:00');
        $this->tableReservation($guest, $restaurant, $table, '2026-08-09', '2026-08-11 09:00:00');

        $this->restaurantOrder($guest, '2026-08-10 09:00:00');
        $this->restaurantOrder($guest, '2026-08-12 09:00:00');
        $this->restaurantOrder($guest, '2026-07-01 09:00:00');

        $stats = $this->statsFor('2026-08-10', '2026-08-12');

        self::assertSame('2', $stats['Active Hotel Bookings']->getValue());
        self::assertSame('2', $stats['Conference Bookings']->getValue());
        self::assertSame('2', $stats['Table Reservations']->getValue());
        self::assertSame('2', $stats['Kitchen Orders']->getValue());
    }

    public function test_admin_kitchen_order_stat_only_counts_orders_eligible_for_the_kitchen_queue(): void
    {
        [$guest] = $this->serviceFixture();

        $this->restaurantOrder($guest, '2026-08-10 09:00:00');
        $this->restaurantOrder($guest, '2026-08-11 09:00:00', 'pending', 'corporate_account');
        $this->restaurantOrder($guest, '2026-08-11 10:00:00', 'pending');
        $this->restaurantOrder($guest, '2026-08-11 11:00:00', 'completed', status: 'served');
        $this->restaurantOrder($guest, '2026-07-31 09:00:00');

        $stats = $this->statsFor('2026-08-10', '2026-08-12');

        self::assertSame('2', $stats['Kitchen Orders']->getValue());
    }

    /**
     * @return array{Guest, Room, ConferenceRoom, Restaurant, RestaurantTable}
     */
    private function serviceFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Admin',
            'last_name' => 'Metrics',
            'email' => 'admin-metrics@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Admin Metrics Room',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'ADMIN-METRIC-1',
            'status' => 'available',
        ]);
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Admin Metrics Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Admin Metrics Restaurant',
            'description' => 'Restaurant fixture for admin dashboard metrics.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'ADMIN-METRIC-T1',
            'capacity' => 4,
        ]);

        return [$guest, $room, $conferenceRoom, $restaurant, $table];
    }

    private function booking(Guest $guest, Room $room, string $checkIn, string $checkOut, string $createdAt): void
    {
        $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function conferenceBooking(Guest $guest, ConferenceRoom $room, string $bookingDate, string $createdAt): void
    {
        $this->createdAt(ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $room->id,
            'booking_date' => $bookingDate,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function tableReservation(Guest $guest, Restaurant $restaurant, RestaurantTable $table, string $reservationDate, string $createdAt): void
    {
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
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function restaurantOrder(
        Guest $guest,
        string $createdAt,
        string $paymentStatus = 'completed',
        ?string $paymentMethod = null,
        string $status = 'confirmed',
    ): void {
        $this->createdAt(RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'ADMIN-METRIC-'.str()->upper(str()->random(12)),
            'subtotal' => 25,
            'total' => 25,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'ordering_channel' => 'web',
        ]), $createdAt);
    }

    /**
     * @return array<string, Stat>
     */
    private function statsFor(string $startDate, string $endDate): array
    {
        $widget = new AdminServiceStats;
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

    private function createdAt(Model $model, string $createdAt): void
    {
        $model->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();
    }
}
