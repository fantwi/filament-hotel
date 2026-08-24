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
use App\Services\CorporatePaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CorporatePaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_offline_payment_clears_each_corporate_transaction_type(): void
    {
        $fixtures = $this->fixtures();

        $transactions = [
            [
                $this->booking($fixtures),
                'bank_transfer',
                'BANK-HOTEL-001',
                'booking_id',
                'paid',
                250.00,
            ],
            [
                $this->conferenceBooking($fixtures),
                'momo',
                'MOMO-CONFERENCE-001',
                'conference_booking_id',
                'paid',
                400.00,
            ],
            [
                $this->reservation($fixtures),
                'cash',
                'CASH-TABLE-001',
                'restaurant_reservation_id',
                'completed',
                75.00,
            ],
            [
                $this->order($fixtures),
                'card',
                'CARD-FOOD-001',
                'restaurant_order_id',
                'completed',
                125.00,
            ],
        ];

        foreach ($transactions as [$transaction, $method, $reference, $paymentLink, $paymentStatus, $amount]) {
            $payment = app(CorporatePaymentService::class)->recordOfflinePayment(
                $transaction,
                $method,
                $reference,
            );

            self::assertSame($transaction->id, $payment->{$paymentLink});
            self::assertSame('completed', $payment->payment_status);
            self::assertSame($method, $payment->method);
            self::assertSame($reference, $payment->transaction_reference);
            self::assertSame($amount, (float) $payment->amount);

            $transaction->refresh();
            self::assertSame($paymentStatus, $transaction->payment_status);
            self::assertSame('confirmed', $transaction->status);
        }

        $this->assertDatabaseCount('payments', 4);
        self::assertSame('card', $transactions[3][0]->payment_method);
    }

    public function test_a_corporate_transaction_cannot_be_settled_twice(): void
    {
        $order = $this->order($this->fixtures());
        $service = app(CorporatePaymentService::class);

        $service->recordOfflinePayment($order, 'cash', 'CASH-FOOD-001');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This transaction has already been paid.');

        $service->recordOfflinePayment($order->fresh(), 'cash', 'CASH-FOOD-002');
    }

    /**
     * Builds the required linked guest, room, conference, and restaurant records.
     *
     * @return array<string, Model>
     */
    private function fixtures(): array
    {
        $organization = CorporateOrganization::create([
            'name' => 'Settlement Test Ltd',
            'credit_limit' => 10_000,
            'is_credit_enabled' => true,
        ]);
        $user = User::factory()->create([
            'department' => 'guest',
            'corporate_organization_id' => $organization->id,
        ]);
        $roomType = RoomType::create([
            'name' => 'Settlement Room',
            'price_per_night' => 125,
            'capacity' => 2,
            'description' => 'A room used to test corporate settlements.',
        ]);
        $room = Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => 'S101',
            'status' => 'available',
        ]);
        $conferenceRoom = ConferenceRoom::create([
            'name' => 'Settlement Boardroom',
            'capacity' => 20,
            'price_per_hour' => 200,
            'is_available' => true,
            'is_published' => true,
        ]);
        $restaurant = Restaurant::create([
            'name' => 'Settlement Restaurant',
            'description' => 'A restaurant used to test corporate settlements.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
            'capacity' => 50,
            'is_open' => true,
            'is_published' => true,
        ]);
        $table = RestaurantTable::create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'S1',
            'capacity' => 4,
            'reservation_fee' => 75,
            'status' => 'available',
        ]);

        return [
            'organization' => $organization,
            'guest' => $user->guest,
            'room' => $room,
            'conference_room' => $conferenceRoom,
            'restaurant' => $restaurant,
            'table' => $table,
        ];
    }

    /**
     * Creates an unpaid corporate hotel booking.
     *
     * @param  array<string, Model>  $fixtures
     */
    private function booking(array $fixtures): Booking
    {
        return Booking::create([
            'guest_id' => $fixtures['guest']->id,
            'room_id' => $fixtures['room']->id,
            'corporate_organization_id' => $fixtures['organization']->id,
            'check_in' => today()->addWeek(),
            'check_out' => today()->addWeek()->addDays(2),
            'total_price' => 250,
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'hold_status' => 'confirmed',
        ]);
    }

    /**
     * Creates an unpaid corporate conference booking.
     *
     * @param  array<string, Model>  $fixtures
     */
    private function conferenceBooking(array $fixtures): ConferenceBooking
    {
        return ConferenceBooking::create([
            'conference_room_id' => $fixtures['conference_room']->id,
            'guest_id' => $fixtures['guest']->id,
            'corporate_organization_id' => $fixtures['organization']->id,
            'booking_date' => today()->addWeek(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 400,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Creates an unpaid corporate table reservation.
     *
     * @param  array<string, Model>  $fixtures
     */
    private function reservation(array $fixtures): RestaurantReservation
    {
        return RestaurantReservation::create([
            'restaurant_id' => $fixtures['restaurant']->id,
            'restaurant_table_id' => $fixtures['table']->id,
            'guest_id' => $fixtures['guest']->id,
            'corporate_organization_id' => $fixtures['organization']->id,
            'guest_name' => $fixtures['guest']->full_name,
            'guest_email' => $fixtures['guest']->email,
            'guest_phone' => '0200000000',
            'reservation_date' => today()->addWeek(),
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 75,
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'hold_status' => 'confirmed',
        ]);
    }

    /**
     * Creates an unpaid corporate food order.
     *
     * @param  array<string, Model>  $fixtures
     */
    private function order(array $fixtures): RestaurantOrder
    {
        return RestaurantOrder::create([
            'guest_id' => $fixtures['guest']->id,
            'corporate_organization_id' => $fixtures['organization']->id,
            'order_number' => 'FOOD-SETTLEMENT-001',
            'customer_email' => $fixtures['guest']->email,
            'subtotal' => 125,
            'discount' => 0,
            'vat' => 0,
            'nhil' => 0,
            'tax' => 0,
            'service_charge' => 0,
            'total' => 125,
            'payment_method' => 'corporate_account',
            'payment_status' => 'pending',
            'status' => 'confirmed',
            'ordering_channel' => 'web',
        ]);
    }
}
