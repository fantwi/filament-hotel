<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\AccountantReceivablesStats;
use App\Filament\Admin\Widgets\AccountantStats;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\Guest;
use App\Models\Payment;
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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountantStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_received_counts_only_collected_payments_in_the_selected_range(): void
    {
        $this->payment('paid', 'PAID-001', '2026-09-01 09:00:00');
        $this->payment('completed', 'COMPLETED-001', '2026-09-02 09:00:00');
        $this->payment('pending', 'PENDING-001', '2026-09-02 10:00:00');
        $this->payment('unpaid', 'UNPAID-001', '2026-09-02 11:00:00');
        $this->payment('refunded', 'REFUNDED-001', '2026-09-02 12:00:00');
        $this->payment('paid', 'OUTSIDE-RANGE-001', '2026-08-31 23:59:59');

        $widget = new AccountantStats;
        $widget->pageFilters = [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
        ];

        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);
        $stats = collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat]);

        self::assertSame('2', $stats['Payments Received']->getValue());
    }

    public function test_outstanding_balance_matches_valid_channel_receivables_for_the_selected_range(): void
    {
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();

        $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]));
        $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-12',
            'check_out' => '2026-09-13',
            'total_price' => 900,
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ]));
        $this->createdAt(ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-09-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]));
        $this->createdAt(ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-09-11',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 900,
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ]));
        $this->createdAt(RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => 'Accountant Test Guest',
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-09-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 300,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]));
        $this->createdAt(RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => 'Accountant Test Guest',
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-09-11',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 900,
            'status' => 'no_show',
            'payment_status' => 'pending',
        ]));
        $this->createdAt(RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'ACCOUNTANT-VALID-001',
            'total' => 400,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]));
        $this->createdAt(RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'ACCOUNTANT-CANCELLED-001',
            'total' => 900,
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ]));

        $summary = $this->stats(new AccountantStats);
        $channels = $this->stats(new AccountantReceivablesStats);

        self::assertSame('GHS 1,000.00', $summary['Outstanding Balance']->getValue());
        self::assertSame('GHS 100.00', $channels['Hotel booking receivables']->getValue());
        self::assertSame('GHS 200.00', $channels['Conference receivables']->getValue());
        self::assertSame('GHS 300.00', $channels['Table-reservation receivables']->getValue());
        self::assertSame('GHS 400.00', $channels['Food-order receivables']->getValue());
    }

    public function test_receivables_stats_uses_one_aggregate_query_per_transaction_channel(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = $this->stats(new AccountantReceivablesStats);
            $queryCount = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(5, $stats);
        self::assertSame(4, $queryCount);
    }

    private function payment(string $status, string $reference, string $createdAt): void
    {
        Payment::query()->forceCreate([
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => $status,
            'transaction_reference' => $reference,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * @return array{0: Guest, 1: Room, 2: ConferenceRoom, 3: Restaurant, 4: RestaurantTable}
     */
    private function serviceFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Accountant',
            'last_name' => 'Test Guest',
            'email' => 'accountant-stats@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Accountant Test Room',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'ACCOUNTANT-1',
            'status' => 'available',
        ]);
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Accountant Test Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Accountant Test Restaurant',
            'description' => 'Restaurant fixture for accountant dashboard stats.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'ACCOUNTANT-T1',
            'capacity' => 4,
        ]);

        return [$guest, $room, $conferenceRoom, $restaurant, $table];
    }

    private function createdAt(Model $model): void
    {
        $model->forceFill([
            'created_at' => Carbon::parse('2026-09-02 09:00:00'),
            'updated_at' => Carbon::parse('2026-09-02 09:00:00'),
        ])->saveQuietly();
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(object $widget): array
    {
        $widget->pageFilters = [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
        ];

        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }
}
