<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\RestaurantReservation;
use Carbon\Carbon;
use Tests\TestCase;

class CorporateCheckoutTimerTest extends TestCase
{
    public function test_corporate_checkouts_do_not_render_a_hold_timer(): void
    {
        $hotel = new Booking(['corporate_organization_id' => 1, 'total_price' => 100]);
        $hotel->id = 1;

        $conference = new ConferenceBooking([
            'corporate_organization_id' => 1,
            'booking_date' => Carbon::tomorrow(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
        ]);
        $conference->id = 1;
        $conference->setRelation('room', new ConferenceRoom(['name' => 'Boardroom']));

        $reservation = new RestaurantReservation([
            'corporate_organization_id' => 1,
            'reservation_date' => Carbon::tomorrow(),
            'reservation_time' => '18:00',
            'reservation_fee' => 50,
        ]);
        $reservation->id = 1;

        $this->view('booking.payment', compact('hotel') + ['booking' => $hotel])
            ->assertDontSee('Room Reserved Temporarily')
            ->assertDontSee('holdTimer');

        $this->view('conference.payment', ['booking' => $conference])
            ->assertDontSee('Conference room reserved temporarily')
            ->assertDontSee('holdTimer');

        $this->view('restaurant.payment', ['reservation' => $reservation, 'accessToken' => null])
            ->assertDontSee('Reservation expires in')
            ->assertDontSee('let expires');
    }
}
