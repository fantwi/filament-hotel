<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Tests\TestCase;

class PaymentTransactionDisplayTest extends TestCase
{
    public function test_payment_transaction_labels_and_guests_cover_all_payment_workflows(): void
    {
        $guest = new Guest(['first_name' => 'Ama', 'last_name' => 'Mensah', 'email' => 'ama@example.test']);
        $guest->id = 42;

        $cases = [
            [new Payment(['booking_id' => 11]), new Booking(['guest_id' => $guest->id]), 'booking', 'Hotel booking #11'],
            [new Payment(['conference_booking_id' => 12]), new ConferenceBooking(['guest_id' => $guest->id]), 'conferenceBooking', 'Conference booking #12'],
            [new Payment(['restaurant_reservation_id' => 13]), new RestaurantReservation(['guest_id' => $guest->id]), 'restaurantReservation', 'Table reservation #13'],
            [new Payment(['restaurant_order_id' => 14]), new RestaurantOrder(['guest_id' => $guest->id]), 'restaurantOrder', 'Food order #14'],
        ];

        foreach ($cases as [$payment, $transaction, $relation, $label]) {
            $transaction->setRelation('guest', $guest);
            $payment->setRelation($relation, $transaction);

            self::assertSame($label, $payment->transactionLabel());
            self::assertSame($guest, $payment->transactionGuest());
            self::assertSame('Ama Mensah', $payment->transactionGuestName());
        }
    }

    public function test_direct_guest_link_is_used_when_available(): void
    {
        $guest = new Guest(['first_name' => 'Kofi', 'last_name' => 'Owusu']);
        $payment = new Payment(['guest_id' => 43]);
        $payment->setRelation('guest', $guest);

        self::assertSame($guest, $payment->transactionGuest());
        self::assertSame('Kofi Owusu', $payment->transactionGuestName());
    }
}
