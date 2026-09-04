<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\GuestReport;
use App\Filament\Admin\Widgets\GuestStats;
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
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_report_uses_one_selected_period_for_structured_guest_data(): void
    {
        $reportPage = new GuestReport;
        $reportPage->period = 'yearly';

        self::assertSame('Yearly', $reportPage->periodLabel());

        $report = $reportPage->report();

        self::assertArrayHasKey('payingGuests', $report);
        self::assertArrayHasKey('returningGuests', $report);
        self::assertArrayHasKey('activity', $report);
        self::assertArrayHasKey('food', $report['activity']);
    }

    public function test_guest_report_calculates_paid_guest_spend_for_the_selected_period(): void
    {
        $guest = Guest::query()->create([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'phone_number' => '0240000000',
            'email' => 'ama@example.test',
        ]);

        Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => 125.50,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-TEST',
        ]);

        $report = (new GuestReport)->report();

        self::assertSame(1, $report['payingGuests']);
        self::assertSame(125.5, $report['averageSpend']);
        self::assertSame(125.5, $report['topGuests']->first()->total_spend);
        self::assertSame($guest->id, $report['topGuests']->first()->guest->id);
    }

    public function test_guest_report_attributes_payments_through_every_supported_transaction_source(): void
    {
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();
        $hotel = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $conference = ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-09-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $reservation = RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->full_name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-09-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 300,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);
        $foodOrder = RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'GUEST-REPORT-INDIRECT-ORDER',
            'total' => 400,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);

        foreach ([
            ['booking_id', $hotel->id, 100, 'GUEST-REPORT-INDIRECT-HOTEL'],
            ['conference_booking_id', $conference->id, 200, 'GUEST-REPORT-INDIRECT-CONFERENCE'],
            ['restaurant_reservation_id', $reservation->id, 300, 'GUEST-REPORT-INDIRECT-TABLE'],
            ['restaurant_order_id', $foodOrder->id, 400, 'GUEST-REPORT-INDIRECT-FOOD'],
        ] as [$foreignKey, $foreignId, $amount, $reference]) {
            Payment::query()->create([
                $foreignKey => $foreignId,
                'guest_id' => null,
                'amount' => $amount,
                'method' => 'cash',
                'payment_status' => 'completed',
                'transaction_reference' => $reference,
            ]);
        }

        Payment::query()->create([
            'guest_id' => null,
            'amount' => 999,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-TRULY-UNLINKED',
        ]);

        $report = (new GuestReport)->report();

        self::assertSame(1, $report['payingGuests']);
        self::assertSame(1000.0, $report['totalPaid']);
        self::assertSame(4, $report['paymentCount']);
        self::assertSame([
            'hotel' => 1,
            'conference' => 1,
            'table' => 1,
            'food' => 1,
        ], $report['activity']);
        self::assertCount(1, $report['topGuests']);
        self::assertSame($guest->id, $report['topGuests']->first()->guest->id);
        self::assertEqualsWithDelta(1000.0, (float) $report['topGuests']->first()->total_spend, 0.001);
    }

    public function test_guest_report_prefers_the_payment_guest_over_the_source_transaction_guest(): void
    {
        [$sourceGuest, $room] = $this->serviceFixture();
        $paymentGuest = Guest::query()->create([
            'first_name' => 'Direct',
            'last_name' => 'Guest',
            'email' => 'direct-guest@example.test',
            'phone_number' => '0240000001',
        ]);
        $hotel = Booking::query()->create([
            'guest_id' => $sourceGuest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-12',
            'check_out' => '2026-09-13',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        Payment::query()->create([
            'booking_id' => $hotel->id,
            'guest_id' => $paymentGuest->id,
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-DIRECT-PRIORITY',
        ]);

        $report = (new GuestReport)->report();

        self::assertSame(1, $report['payingGuests']);
        self::assertSame($paymentGuest->id, $report['topGuests']->first()->guest->id);
    }

    public function test_returning_guest_requires_two_distinct_paid_service_visits(): void
    {
        [$guest, $room] = $this->serviceFixture();
        $firstStay = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $secondStay = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-12',
            'check_out' => '2026-09-13',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        foreach ([40, 60] as $index => $amount) {
            Payment::query()->create([
                'booking_id' => $firstStay->id,
                'guest_id' => $guest->id,
                'amount' => $amount,
                'method' => 'cash',
                'payment_status' => 'completed',
                'transaction_reference' => 'GUEST-REPORT-INSTALMENT-'.$index,
            ]);
        }

        self::assertSame(0, (new GuestReport)->report()['returningGuests']);

        Payment::query()->create([
            'booking_id' => $secondStay->id,
            'guest_id' => $guest->id,
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-SECOND-STAY',
        ]);

        self::assertSame(1, (new GuestReport)->report()['returningGuests']);
    }

    public function test_returning_guest_counts_service_types_separately_and_ignores_unlinked_payments(): void
    {
        [$returningGuest, $room, $conferenceRoom] = $this->serviceFixture();
        $directPaymentGuest = Guest::query()->create([
            'first_name' => 'Direct',
            'last_name' => 'Only',
            'email' => 'direct-only@example.test',
            'phone_number' => '0240000002',
        ]);
        $stay = Booking::query()->create([
            'guest_id' => $returningGuest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $conference = ConferenceBooking::query()->create([
            'guest_id' => $returningGuest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-09-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        foreach ([
            ['booking_id', $stay->id, $returningGuest->id, 'GUEST-REPORT-VISIT-HOTEL'],
            ['conference_booking_id', $conference->id, $returningGuest->id, 'GUEST-REPORT-VISIT-CONFERENCE'],
            [null, null, $directPaymentGuest->id, 'GUEST-REPORT-DIRECT-ONE'],
            [null, null, $directPaymentGuest->id, 'GUEST-REPORT-DIRECT-TWO'],
        ] as [$foreignKey, $foreignId, $guestId, $reference]) {
            $payment = [
                'guest_id' => $guestId,
                'amount' => 100,
                'method' => 'cash',
                'payment_status' => 'completed',
                'transaction_reference' => $reference,
            ];

            if ($foreignKey !== null) {
                $payment[$foreignKey] = $foreignId;
            }

            Payment::query()->create($payment);
        }

        self::assertSame($stay->id, $conference->id);
        self::assertSame(1, (new GuestReport)->report()['returningGuests']);
    }

    public function test_guest_stats_widget_uses_period_aware_overview_stats(): void
    {
        self::assertTrue(is_subclass_of(GuestStats::class, StatsOverviewWidget::class));

        $widget = new GuestStats;
        $widget->period = 'yearly';
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertCount(5, $stats);
    }

    /**
     * @return array{0: Guest, 1: Room, 2: ConferenceRoom, 3: Restaurant, 4: RestaurantTable}
     */
    private function serviceFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Guest',
            'last_name' => 'Attribution',
            'email' => 'guest-attribution@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Guest Report Room Type',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'GUEST-REPORT-101',
            'status' => 'available',
        ]);
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Guest Report Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Guest Report Restaurant',
            'description' => 'Restaurant fixture for guest attribution tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'GUEST-REPORT-T1',
            'capacity' => 4,
        ]);

        return [$guest, $room, $conferenceRoom, $restaurant, $table];
    }
}
