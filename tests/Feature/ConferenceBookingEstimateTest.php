<?php

namespace Tests\Feature;

use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConferenceBookingEstimateTest extends TestCase
{
    use RefreshDatabase;

    public function test_applying_a_discount_returns_a_billing_estimate_without_creating_a_booking(): void
    {
        $user = User::factory()->create(['department' => 'guest']);
        $room = ConferenceRoom::create([
            'name' => 'Boardroom',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
            'is_published' => true,
        ]);
        Promotion::create([
            'name' => 'Conference ten percent',
            'code' => 'CONF10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->from(route('conference.book', $room))
            ->post(route('conference.booking.store'), [
                'conference_room_id' => $room->id,
                'booking_date' => now()->addWeek()->toDateString(),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'attendees' => 10,
                'promotion_code' => 'CONF10',
                'apply_discount' => '1',
            ]);

        $response
            ->assertRedirect(route('conference.book', $room))
            ->assertSessionHas('conference_billing_preview', function (array $preview): bool {
                return $preview['subtotal'] === 200.0
                    && $preview['discount'] === 20.0
                    && $preview['net'] === 180.0
                    && $preview['total'] === 210.6
                    && $preview['promotion_code'] === 'CONF10';
            });

        self::assertSame(0, ConferenceBooking::count());
    }
}
