<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\CorporateOrganization;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\CorporateCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorporateDeferredPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_corporate_guest_confirms_a_room_booking_without_visiting_paystack(): void
    {
        $user = $this->corporateUser();
        $type = RoomType::create([
            'name' => 'Corporate Suite',
            'price_per_night' => 100,
            'capacity' => 2,
            'description' => 'A room for corporate stays.',
            'is_published' => true,
        ]);
        $room = Room::create([
            'room_type_id' => $type->id,
            'room_number' => '901',
            'status' => 'available',
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'booking.room_id' => $room->id,
                'booking.room_price' => 100,
            ])
            ->post('/booking/details', [
                'check_in' => today()->addWeek()->toDateString(),
                'check_out' => today()->addWeek()->addDays(2)->toDateString(),
                'check_in_time' => '14:00',
                'check_out_time' => '11:00',
            ]);

        $response->assertRedirect(route('dashboard'));
        $booking = Booking::firstOrFail();
        self::assertSame($user->corporate_organization_id, $booking->corporate_organization_id);
        self::assertSame('confirmed', $booking->status);
        self::assertSame('confirmed', $booking->hold_status);
        self::assertSame('pending', $booking->payment_status);
        self::assertNull($booking->hold_until);
        self::assertSame(234.0, (float) $booking->total_price);
    }

    public function test_corporate_guest_confirms_a_conference_booking_without_immediate_payment(): void
    {
        $user = $this->corporateUser();
        $room = ConferenceRoom::create([
            'name' => 'Corporate Boardroom',
            'capacity' => 30,
            'price_per_hour' => 100,
            'is_available' => true,
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->post(route('conference.booking.store'), [
            'conference_room_id' => $room->id,
            'booking_date' => today()->addWeek()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 12,
        ]);

        $response->assertRedirect(route('dashboard'));
        $booking = ConferenceBooking::firstOrFail();
        self::assertSame($user->corporate_organization_id, $booking->corporate_organization_id);
        self::assertSame('confirmed', $booking->status);
        self::assertSame('pending', $booking->payment_status);
        self::assertNull($booking->hold_until);
    }

    public function test_corporate_guest_confirms_a_table_reservation_without_immediate_payment(): void
    {
        $user = $this->corporateUser();
        $restaurant = Restaurant::create([
            'name' => 'Corporate Dining',
            'description' => 'A restaurant for corporate dining.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
            'capacity' => 50,
            'is_open' => true,
            'is_published' => true,
        ]);
        $table = RestaurantTable::create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'C1',
            'capacity' => 4,
            'reservation_fee' => 100,
            'status' => 'available',
        ]);

        $response = $this->actingAs($user)->post(route('restaurant.reserve.store'), [
            'restaurant_table_id' => $table->id,
            'guest_name' => $user->name,
            'guest_email' => $user->email,
            'guest_phone' => '0200000000',
            'reservation_date' => today()->addWeek()->toDateString(),
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
        ]);

        $response->assertRedirect(route('dashboard'));
        $reservation = RestaurantReservation::firstOrFail();
        self::assertSame($user->corporate_organization_id, $reservation->corporate_organization_id);
        self::assertSame('confirmed', $reservation->status);
        self::assertSame('confirmed', $reservation->hold_status);
        self::assertSame('pending', $reservation->payment_status);
        self::assertNull($reservation->hold_until);
    }

    public function test_corporate_guest_food_order_is_sent_to_the_kitchen_without_paystack(): void
    {
        $user = $this->corporateUser();
        $category = MenuCategory::create([
            'name' => 'Corporate Lunch',
            'slug' => 'corporate-lunch',
            'is_active' => true,
        ]);
        $item = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Jollof Lunch',
            'slug' => 'jollof-lunch',
            'price' => 100,
            'is_available' => true,
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['cart' => [$item->id => ['quantity' => 2]]])
            ->post(route('restaurant.checkout.store'), [
                'email' => $user->email,
            ]);

        $order = RestaurantOrder::firstOrFail();
        $response->assertRedirect(route('restaurant.orders.confirmation', $order));
        self::assertSame($user->corporate_organization_id, $order->corporate_organization_id);
        self::assertSame('corporate_account', $order->payment_method);
        self::assertSame('confirmed', $order->status);
        self::assertSame('pending', $order->payment_status);
        self::assertTrue($order->isKitchenEligible());
    }

    public function test_corporate_credit_limit_includes_unpaid_corporate_orders(): void
    {
        $organization = CorporateOrganization::create([
            'name' => 'Limited Corporate Ltd',
            'credit_limit' => 100,
            'is_credit_enabled' => true,
        ]);

        RestaurantOrder::create([
            'corporate_organization_id' => $organization->id,
            'order_number' => 'CORPORATE-CREDIT-001',
            'customer_email' => 'accounts@example.test',
            'payment_method' => 'corporate_account',
            'payment_status' => 'pending',
            'status' => 'confirmed',
            'total' => 100,
            'ordering_channel' => 'web',
        ]);

        $credit = app(CorporateCreditService::class);

        self::assertSame(100.0, $credit->outstandingBalance($organization));
        self::assertFalse($credit->canCharge($organization, 0.01));
    }

    private function corporateUser(): User
    {
        $organization = CorporateOrganization::create([
            'name' => 'Example Corporate Ltd',
            'credit_limit' => 10000,
            'payment_terms_days' => 30,
            'is_credit_enabled' => true,
        ]);

        return User::factory()->create([
            'department' => 'guest',
            'corporate_organization_id' => $organization->id,
        ]);
    }
}
