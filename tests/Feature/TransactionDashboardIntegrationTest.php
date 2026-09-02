<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\TransactionOverview;
use App\Filament\Admin\Widgets\TransactionStats;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\CorporateOrganization;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class TransactionDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const FILTERS = [
        'period' => 'custom',
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-31',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    #[DataProvider('authorizedStaffDepartments')]
    public function test_authorized_finance_roles_can_render_the_dashboard_in_widget_order(string $department): void
    {
        $this->actingAs($this->staff($department));

        $this->get('/admin/transaction-dashboard')
            ->assertOk()
            ->assertSeeInOrder([
                'Dashboard period',
                TransactionStats::class,
                'Finance',
                TransactionOverview::class,
            ], escape: false);
    }

    public function test_reception_staff_cannot_open_the_transaction_dashboard(): void
    {
        $this->actingAs($this->staff('reception'))
            ->get('/admin/transaction-dashboard')
            ->assertForbidden();
    }

    public function test_both_widgets_render_the_same_date_filtered_four_channel_totals(): void
    {
        $staff = $this->staff('accounting');
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();
        $organization = CorporateOrganization::query()->create([
            'name' => 'Transaction Dashboard Corporate Account',
            'is_credit_enabled' => true,
        ]);

        $hotel = $this->hotelBooking($guest, $room, 100, '2026-08-05 09:00:00');
        $conference = $this->conferenceBooking($guest, $conferenceRoom, 200, '2026-08-10 09:00:00');
        $reservation = $this->tableReservation($guest, $restaurant, $table, 300, '2026-08-15 09:00:00');
        $food = $this->foodOrder($guest, 400, 'TXN-IN-RANGE', '2026-08-20 09:00:00', $organization);

        $this->payment($guest, 'booking_id', $hotel->id, 25, 'TXN-HOTEL-PAID', '2026-08-06 10:00:00');
        $this->payment($guest, 'conference_booking_id', $conference->id, 50, 'TXN-CONFERENCE-PAID', '2026-08-11 10:00:00');
        $this->payment($guest, 'restaurant_reservation_id', $reservation->id, 75, 'TXN-TABLE-PAID', '2026-08-16 10:00:00');
        $this->payment($guest, 'restaurant_order_id', $food->id, 100, 'TXN-FOOD-PAID', '2026-08-21 10:00:00');

        $outsideHotel = $this->hotelBooking($guest, $room, 1000, '2026-07-31 23:59:59');
        $outsideConference = $this->conferenceBooking($guest, $conferenceRoom, 2000, '2026-09-01 00:00:00');
        $outsideReservation = $this->tableReservation($guest, $restaurant, $table, 3000, '2026-07-20 09:00:00');
        $outsideFood = $this->foodOrder($guest, 4000, 'TXN-OUTSIDE-RANGE', '2026-09-10 09:00:00');

        $this->payment($guest, 'booking_id', $outsideHotel->id, 1000, 'TXN-OUTSIDE-HOTEL', '2026-07-31 23:59:59');
        $this->payment($guest, 'conference_booking_id', $outsideConference->id, 2000, 'TXN-OUTSIDE-CONFERENCE', '2026-09-01 00:00:00');
        $this->payment($guest, 'restaurant_reservation_id', $outsideReservation->id, 3000, 'TXN-OUTSIDE-TABLE', '2026-07-20 09:00:00');
        $this->payment($guest, 'restaurant_order_id', $outsideFood->id, 4000, 'TXN-OUTSIDE-FOOD', '2026-09-10 09:00:00');

        $stats = $this->stats();
        $overview = $this->overview();

        self::assertSame('4', $stats['Transactions created']->getValue());
        self::assertSame('GHS 1,000.00', $stats['Gross transaction value']->getValue());
        self::assertSame('GHS 250.00', $stats['Payments received']->getValue());
        self::assertSame('GHS 750.00', $stats['Outstanding balance']->getValue());
        self::assertSame('GHS 300.00', $stats['Corporate outstanding']->getValue());
        self::assertSame([
            'transactions' => 4,
            'gross' => 1000.0,
            'payments' => 250.0,
            'payment_count' => 4,
            'outstanding' => 750.0,
            'outstanding_count' => 4,
            'corporate_outstanding' => 300.0,
            'corporate_outstanding_count' => 1,
        ], $overview['totals']);
        self::assertSame([
            ['Hotel bookings', 1, 100.0, 25.0],
            ['Conference bookings', 1, 200.0, 50.0],
            ['Table reservations', 1, 300.0, 75.0],
            ['Food orders', 1, 400.0, 100.0],
        ], collect($overview['rows'])
            ->map(fn (array $row): array => [$row['label'], $row['transactions'], $row['gross'], $row['payments']])
            ->all());

        Livewire::actingAs($staff)
            ->test(TransactionStats::class, ['pageFilters' => self::FILTERS])
            ->assertSee('Transactions created')
            ->assertSee('GHS 1,000.00')
            ->assertSee('GHS 250.00')
            ->assertSee('Aug 1, 2026 - Aug 31, 2026');

        Livewire::actingAs($staff)
            ->test(TransactionOverview::class, ['pageFilters' => self::FILTERS])
            ->assertSee('Hotel bookings')
            ->assertSee('Conference bookings')
            ->assertSee('Table reservations')
            ->assertSee('Food orders')
            ->assertSee('Aug 1, 2026 - Aug 31, 2026');
    }

    public function test_collected_payments_follow_the_payment_date_instead_of_the_parent_transaction_date(): void
    {
        [$guest, $room] = $this->serviceFixture();
        $outsideBooking = $this->hotelBooking($guest, $room, 500, '2026-07-31 23:59:59');
        $insideBooking = $this->hotelBooking($guest, $room, 700, '2026-08-10 09:00:00');

        $this->payment($guest, 'booking_id', $outsideBooking->id, 125, 'TXN-PAYMENT-IN-RANGE', '2026-08-01 00:00:00');
        $this->payment($guest, 'booking_id', $insideBooking->id, 350, 'TXN-PAYMENT-OUTSIDE-RANGE', '2026-09-01 00:00:00');

        $stats = $this->stats();
        $overview = $this->overview();

        self::assertSame('GHS 125.00', $stats['Payments received']->getValue());
        self::assertSame(125.0, $overview['totals']['payments']);
        self::assertSame(1, $overview['totals']['payment_count']);
    }

    public function test_outstanding_values_use_remaining_balances_for_partial_failed_and_overpaid_transactions(): void
    {
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();
        $organization = CorporateOrganization::query()->create([
            'name' => 'Partial Payment Corporate Account',
            'is_credit_enabled' => true,
        ]);

        $hotel = $this->hotelBooking($guest, $room, 1000, '2026-08-05 09:00:00');
        $hotel->forceFill(['payment_status' => 'partially_paid'])->saveQuietly();

        $conference = $this->conferenceBooking($guest, $conferenceRoom, 800, '2026-08-10 09:00:00');
        $conference->forceFill([
            'corporate_organization_id' => $organization->id,
            'payment_status' => 'partial',
        ])->saveQuietly();

        $reservation = $this->tableReservation($guest, $restaurant, $table, 600, '2026-08-15 09:00:00');
        $reservation->forceFill(['payment_status' => 'partial'])->saveQuietly();

        $food = $this->foodOrder($guest, 400, 'TXN-PARTIAL-FOOD', '2026-08-20 09:00:00', $organization);
        $food->forceFill(['payment_status' => 'failed'])->saveQuietly();

        $overpaid = $this->hotelBooking($guest, $room, 100, '2026-08-25 09:00:00');

        $this->payment($guest, 'booking_id', $hotel->id, 250, 'TXN-HOTEL-PARTIAL', '2026-08-06 10:00:00');
        $this->payment($guest, 'conference_booking_id', $conference->id, 300, 'TXN-CONFERENCE-PARTIAL', '2026-08-11 10:00:00');
        $this->payment($guest, 'restaurant_reservation_id', $reservation->id, 100, 'TXN-TABLE-PARTIAL', '2026-08-16 10:00:00');
        $this->payment($guest, 'restaurant_order_id', $food->id, 50, 'TXN-FOOD-PARTIAL', '2026-08-21 10:00:00');
        $this->payment($guest, 'booking_id', $overpaid->id, 150, 'TXN-HOTEL-OVERPAID', '2026-08-26 10:00:00');

        $stats = $this->stats();
        $overview = $this->overview();
        $rows = collect($overview['rows'])->keyBy('label');

        self::assertSame('GHS 2,100.00', $stats['Outstanding balance']->getValue());
        self::assertSame('GHS 850.00', $stats['Corporate outstanding']->getValue());
        self::assertSame(2100.0, $overview['totals']['outstanding']);
        self::assertSame(4, $overview['totals']['outstanding_count']);
        self::assertSame(850.0, $overview['totals']['corporate_outstanding']);
        self::assertSame(2, $overview['totals']['corporate_outstanding_count']);
        self::assertSame(750.0, $rows['Hotel bookings']['outstanding']);
        self::assertSame(500.0, $rows['Conference bookings']['outstanding']);
        self::assertSame(500.0, $rows['Table reservations']['outstanding']);
        self::assertSame(350.0, $rows['Food orders']['outstanding']);
    }

    public function test_cancelled_transactions_are_excluded_from_gross_and_outstanding_values(): void
    {
        [$guest, $room] = $this->serviceFixture();
        $this->hotelBooking($guest, $room, 100, '2026-08-10 09:00:00');
        $this->hotelBooking($guest, $room, 900, '2026-08-11 09:00:00', 'cancelled');

        $stats = $this->stats();
        $overview = $this->overview();

        self::assertSame('GHS 100.00', $stats['Gross transaction value']->getValue());
        self::assertSame('GHS 100.00', $stats['Outstanding balance']->getValue());
        self::assertSame(100.0, $overview['totals']['gross']);
        self::assertSame(100.0, $overview['totals']['outstanding']);
        self::assertSame(1, $overview['totals']['outstanding_count']);
    }

    public static function authorizedStaffDepartments(): array
    {
        return [
            'super admin' => ['super_admin'],
            'admin' => ['admin'],
            'manager' => ['management'],
            'accountant' => ['accounting'],
        ];
    }

    private function staff(string $department): User
    {
        return User::factory()->create(['department' => $department]);
    }

    /**
     * @return array{0: Guest, 1: Room, 2: ConferenceRoom, 3: Restaurant, 4: RestaurantTable}
     */
    private function serviceFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Transaction',
            'last_name' => 'Dashboard Guest',
            'email' => 'transaction-dashboard@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Transaction Dashboard Room Type',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'TRANSACTION-101',
            'status' => 'available',
        ]);
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Transaction Dashboard Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Transaction Dashboard Restaurant',
            'description' => 'Restaurant fixture for transaction dashboard tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'TRANSACTION-T1',
            'capacity' => 4,
        ]);

        return [$guest, $room, $conferenceRoom, $restaurant, $table];
    }

    private function hotelBooking(
        Guest $guest,
        Room $room,
        float $amount,
        string $createdAt,
        string $status = 'confirmed',
    ): Booking {
        return $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-11',
            'total_price' => $amount,
            'status' => $status,
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function conferenceBooking(
        Guest $guest,
        ConferenceRoom $room,
        float $amount,
        string $createdAt,
    ): ConferenceBooking {
        return $this->createdAt(ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $room->id,
            'booking_date' => '2026-10-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => $amount,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function tableReservation(
        Guest $guest,
        Restaurant $restaurant,
        RestaurantTable $table,
        float $amount,
        string $createdAt,
    ): RestaurantReservation {
        return $this->createdAt(RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->full_name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-10-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => $amount,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function foodOrder(
        Guest $guest,
        float $amount,
        string $orderNumber,
        string $createdAt,
        ?CorporateOrganization $organization = null,
    ): RestaurantOrder {
        return $this->createdAt(RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'corporate_organization_id' => $organization?->id,
            'order_number' => $orderNumber,
            'total' => $amount,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function payment(
        Guest $guest,
        string $foreignKey,
        int $foreignId,
        float $amount,
        string $reference,
        string $createdAt,
    ): Payment {
        return $this->createdAt(Payment::query()->create([
            'guest_id' => $guest->id,
            $foreignKey => $foreignId,
            'amount' => $amount,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => $reference,
        ]), $createdAt);
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

    /**
     * @return array<string, Stat>
     */
    private function stats(): array
    {
        $widget = new TransactionStats;
        $widget->pageFilters = self::FILTERS;
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    /**
     * @return array{rows: array<int, array<string, int|float|string>>, periodLabel: string, totals: array<string, int|float>}
     */
    private function overview(): array
    {
        $widget = new TransactionOverview;
        $widget->pageFilters = self::FILTERS;
        $method = new ReflectionMethod($widget, 'getViewData');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }
}
