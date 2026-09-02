<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\CorporateOrganization;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\CorporateCreditService;
use App\Services\CorporatePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorporateBillingDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_corporate_dashboard_overview_combines_all_four_deferred_payment_flows(): void
    {
        $organization = CorporateOrganization::create([
            'name' => 'Dashboard Corporate Ltd',
            'credit_limit' => 1000,
            'is_credit_enabled' => true,
        ]);
        $user = User::factory()->create([
            'department' => 'guest',
            'corporate_organization_id' => $organization->id,
        ]);

        $type = RoomType::create([
            'name' => 'Dashboard Room Type',
            'price_per_night' => 100,
            'capacity' => 2,
            'description' => 'Dashboard test room.',
        ]);
        $room = Room::create([
            'room_type_id' => $type->id,
            'room_number' => 'D101',
            'status' => 'available',
        ]);
        $hotel = Booking::create([
            'guest_id' => $user->guest->id,
            'room_id' => $room->id,
            'corporate_organization_id' => $organization->id,
            'check_in' => today()->addWeek(),
            'check_out' => today()->addWeek()->addDay(),
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'hold_status' => 'confirmed',
        ]);

        $conferenceRoom = ConferenceRoom::create([
            'name' => 'Dashboard Boardroom',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
            'is_published' => true,
        ]);
        $conference = ConferenceBooking::create([
            'conference_room_id' => $conferenceRoom->id,
            'guest_id' => $user->guest->id,
            'corporate_organization_id' => $organization->id,
            'booking_date' => today()->addWeek(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        $restaurant = Restaurant::create([
            'name' => 'Dashboard Restaurant',
            'description' => 'Dashboard test restaurant.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
            'capacity' => 50,
            'is_open' => true,
            'is_published' => true,
        ]);
        $table = RestaurantTable::create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'D1',
            'capacity' => 4,
            'reservation_fee' => 50,
            'status' => 'available',
        ]);
        $reservation = RestaurantReservation::create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $user->guest->id,
            'corporate_organization_id' => $organization->id,
            'guest_name' => $user->name,
            'guest_email' => $user->email,
            'guest_phone' => '0200000000',
            'reservation_date' => today()->addWeek(),
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 50,
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'hold_status' => 'confirmed',
            'duration_minutes' => 120,
        ]);

        $order = RestaurantOrder::create([
            'guest_id' => $user->guest->id,
            'corporate_organization_id' => $organization->id,
            'order_number' => 'DASHBOARD-CORPORATE-ORDER',
            'customer_email' => $user->email,
            'payment_method' => 'corporate_account',
            'payment_status' => 'pending',
            'status' => 'confirmed',
            'total' => 25,
            'ordering_channel' => 'web',
        ]);

        $overview = app(CorporateCreditService::class)->dashboardOverview();

        self::assertSame(1, $overview['active_accounts']);
        self::assertSame(1, $overview['linked_guests']);
        self::assertSame(375.0, (float) $overview['billed_in_period']);
        self::assertSame(375.0, (float) $overview['period_outstanding']);
        self::assertSame(375.0, (float) $overview['outstanding']);
        self::assertSame(625.0, (float) $overview['available_credit']);
        self::assertSame('Dashboard Corporate Ltd', $overview['accounts']->first()['name']);
        self::assertSame(375.0, (float) $overview['accounts']->first()['outstanding']);

        $payments = app(CorporatePaymentService::class);
        $payments->recordOfflinePayment($hotel, 'bank_transfer', 'BANK-HOTEL');
        $payments->recordOfflinePayment($conference, 'cash', 'CASH-CONFERENCE');
        $payments->recordOfflinePayment($reservation, 'momo', 'MOMO-TABLE');
        $payments->recordOfflinePayment($order, 'card', 'CARD-FOOD');

        self::assertSame('paid', $hotel->refresh()->payment_status);
        self::assertSame('paid', $conference->refresh()->payment_status);
        self::assertSame('completed', $reservation->refresh()->payment_status);
        self::assertSame('completed', $order->refresh()->payment_status);
        self::assertDatabaseCount('payments', 4);
    }
}
