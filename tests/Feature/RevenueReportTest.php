<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RevenueReport;
use App\Filament\Admin\Widgets\RevenueReportStats;
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
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_report_uses_one_selected_period_for_structured_financial_data(): void
    {
        $reportPage = new RevenueReport;
        $reportPage->period = 'quarterly';

        self::assertSame('Quarterly', $reportPage->periodLabel());

        $report = $reportPage->report();

        self::assertArrayHasKey('netRevenue', $report);
        self::assertArrayHasKey('outstandingBreakdown', $report);
        self::assertArrayHasKey('paymentsReceived', $report);
        self::assertArrayHasKey('food', $report['outstandingBreakdown']);
    }

    public function test_revenue_stats_widget_uses_period_aware_overview_stats(): void
    {
        self::assertTrue(is_subclass_of(RevenueReportStats::class, StatsOverviewWidget::class));

        $widget = new RevenueReportStats;
        $widget->period = 'quarterly';
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertCount(4, $stats);
    }

    public function test_outstanding_breakdown_uses_remaining_balances_after_successful_payments(): void
    {
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();

        $hotel = $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-11',
            'total_price' => 1000,
            'status' => 'confirmed',
            'payment_status' => 'partially_paid',
        ]), '2026-08-05 09:00:00');
        $conference = $this->createdAt(ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-10-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 800,
            'status' => 'confirmed',
            'payment_status' => 'partial',
        ]), '2026-08-10 09:00:00');
        $reservation = $this->createdAt(RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->full_name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-10-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 600,
            'status' => 'confirmed',
            'payment_status' => 'partial',
        ]), '2026-08-15 09:00:00');
        $food = $this->createdAt(RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'REVENUE-REPORT-PARTIAL',
            'total' => 400,
            'status' => 'confirmed',
            'payment_status' => 'failed',
        ]), '2026-08-20 09:00:00');
        $overpaid = $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-12',
            'check_out' => '2026-10-13',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]), '2026-08-25 09:00:00');

        $this->payment($guest, 'booking_id', $hotel->id, 250, 'REVENUE-HOTEL-PARTIAL');
        $this->payment($guest, 'conference_booking_id', $conference->id, 300, 'REVENUE-CONFERENCE-PARTIAL');
        $this->payment($guest, 'restaurant_reservation_id', $reservation->id, 100, 'REVENUE-TABLE-PARTIAL');
        $this->payment($guest, 'restaurant_order_id', $food->id, 50, 'REVENUE-FOOD-PARTIAL');
        $this->payment($guest, 'booking_id', $overpaid->id, 150, 'REVENUE-HOTEL-OVERPAID');

        $reportPage = new RevenueReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';
        $report = $reportPage->report();

        self::assertSame([
            'hotel' => 750.0,
            'conference' => 500.0,
            'table' => 500.0,
            'food' => 350.0,
        ], $report['outstandingBreakdown']);
        self::assertSame(2100.0, $report['outstanding']);
    }

    public function test_no_show_conference_booking_is_excluded_from_outstanding_balances(): void
    {
        [$guest, , $conferenceRoom] = $this->serviceFixture();
        $this->createdAt(ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-10-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 800,
            'status' => 'no_show',
            'payment_status' => 'pending',
        ]), '2026-08-10 09:00:00');

        $reportPage = new RevenueReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';

        self::assertSame(0.0, $reportPage->report()['outstandingBreakdown']['conference']);
    }

    /**
     * @return array{0: Guest, 1: Room, 2: ConferenceRoom, 3: Restaurant, 4: RestaurantTable}
     */
    private function serviceFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Revenue',
            'last_name' => 'Report Guest',
            'email' => 'revenue-report@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Revenue Report Room Type',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'REVENUE-101',
            'status' => 'available',
        ]);
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Revenue Report Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Revenue Report Restaurant',
            'description' => 'Restaurant fixture for revenue report tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'REVENUE-T1',
            'capacity' => 4,
        ]);

        return [$guest, $room, $conferenceRoom, $restaurant, $table];
    }

    private function payment(
        Guest $guest,
        string $foreignKey,
        int $foreignId,
        float $amount,
        string $reference,
    ): Payment {
        return Payment::query()->create([
            'guest_id' => $guest->id,
            $foreignKey => $foreignId,
            'amount' => $amount,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => $reference,
        ]);
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    private function createdAt(Model $model, string $createdAt): Model
    {
        $model->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();

        return $model;
    }
}
