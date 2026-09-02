<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\ReceptionArrivals;
use App\Filament\Admin\Widgets\ReceptionDeskStats;
use App\Filament\Admin\Widgets\ReceptionStats;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ReceptionDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_arrival_balance_uses_the_remaining_balance_for_partially_paid_valid_bookings(): void
    {
        [$guest, $room] = $this->hotelFixture();
        $partiallyPaid = $this->booking($guest, $room, 1000, 'confirmed', 'partial');
        $this->booking($guest, $room, 500, 'pending', 'pending');
        $this->booking($guest, $room, 900, 'cancelled', 'unpaid');

        Payment::query()->create([
            'booking_id' => $partiallyPaid->id,
            'guest_id' => $guest->id,
            'amount' => 250,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'RECEPTION-PARTIAL-PAID',
        ]);
        Payment::query()->create([
            'booking_id' => $partiallyPaid->id,
            'guest_id' => $guest->id,
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => 'pending',
            'transaction_reference' => 'RECEPTION-PARTIAL-PENDING',
        ]);

        $stats = $this->stats(new ReceptionDeskStats);

        self::assertSame('GHS 1,250.00', $stats['Unpaid arrival balance']->getValue());
    }

    public function test_reception_service_totals_exclude_cancelled_expired_and_no_show_venue_activity(): void
    {
        [$guest] = $this->hotelFixture();
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Reception Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Reception Restaurant',
            'description' => 'Reception dashboard restaurant fixture.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'RECEPTION-T1',
            'capacity' => 4,
        ]);

        foreach (['confirmed', 'cancelled', 'expired', 'no_show'] as $status) {
            ConferenceBooking::query()->create([
                'guest_id' => $guest->id,
                'conference_room_id' => $conferenceRoom->id,
                'booking_date' => '2026-08-10',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'attendees' => 10,
                'total_price' => 200,
                'status' => $status,
                'payment_status' => 'pending',
            ]);
        }

        foreach (['confirmed', 'cancelled', 'no_show'] as $status) {
            RestaurantReservation::query()->create([
                'restaurant_id' => $restaurant->id,
                'restaurant_table_id' => $table->id,
                'guest_id' => $guest->id,
                'guest_name' => $guest->full_name,
                'guest_email' => $guest->email,
                'guest_phone' => $guest->phone_number,
                'reservation_date' => '2026-08-10',
                'reservation_time' => '18:00',
                'number_of_guests' => 2,
                'reservation_fee' => 50,
                'status' => $status,
                'payment_status' => 'pending',
            ]);
        }

        $stats = $this->stats(new ReceptionStats);

        self::assertSame([
            'Conference events',
            'Table reservations',
        ], array_keys($stats));
        self::assertSame(1, $stats['Conference events']->getValue());
        self::assertSame(1, $stats['Table reservations']->getValue());
    }

    public function test_reception_stats_rows_have_distinct_operational_metrics(): void
    {
        $deskStats = $this->stats(new ReceptionDeskStats);
        $serviceStats = $this->stats(new ReceptionStats);

        self::assertSame([
            'Checked-in stays',
            'Pending arrivals',
            'Pending departures',
            'Unpaid arrival balance',
        ], array_keys($deskStats));
        self::assertSame([], array_intersect(array_keys($deskStats), array_keys($serviceStats)));
    }

    public function test_checked_in_stays_use_the_standard_exclusive_checkout_boundary(): void
    {
        [$guest, $room] = $this->hotelFixture();
        $this->booking($guest, $room, 100, 'checked_in', 'paid', '2026-08-08', '2026-08-10');
        $this->booking($guest, $room, 100, 'checked_in', 'paid', '2026-08-09', '2026-08-11');
        $this->booking($guest, $room, 100, 'checked_in', 'paid', '2026-08-13', '2026-08-14');

        $stats = $this->stats(new ReceptionDeskStats, '2026-08-10', '2026-08-12');

        self::assertSame('1', $stats['Checked-in stays']->getValue());
        self::assertSame('Aug 10, 2026 - Aug 12, 2026', $stats['Checked-in stays']->getDescription());
    }

    public function test_arrivals_table_labels_its_selected_reporting_period_instead_of_calling_it_today(): void
    {
        $widget = new ReceptionArrivals;
        $widget->pageFilters = [
            'period' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ];

        $table = $widget->table(Table::make($this->createMock(HasTable::class)));

        self::assertSame('Hotel Arrivals', $table->getHeading());
        self::assertSame('Scheduled arrivals for Aug 1, 2026 - Aug 31, 2026', $table->getDescription());
    }

    /**
     * @return array{Guest, Room}
     */
    private function hotelFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Reception',
            'last_name' => 'Dashboard',
            'email' => 'reception-dashboard@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Reception Room Type',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'RECEPTION-101',
            'status' => 'available',
        ]);

        return [$guest, $room];
    }

    private function booking(
        Guest $guest,
        Room $room,
        float $total,
        string $status,
        string $paymentStatus,
        string $checkIn = '2026-08-10',
        string $checkOut = '2026-08-12',
    ): Booking {
        return Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_price' => $total,
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(object $widget, string $startDate = '2026-08-01', string $endDate = '2026-08-31'): array
    {
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
}
