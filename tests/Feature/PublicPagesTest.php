<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_public_pages_are_available_without_seeded_content(): void
    {
        foreach ([
            '/',
            '/contact',
            '/rooms',
            '/conference-rooms',
            '/restaurant',
            '/restaurant/menu',
            '/restaurant/tables',
            '/restaurant/gallery',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_guest_cannot_view_another_guests_restaurant_reservation(): void
    {
        $owner = User::factory()->create(['department' => 'guest']);
        $otherGuest = User::factory()->create(['department' => 'guest']);

        $restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'description' => 'Test description',
            'opening_time' => '09:00',
            'closing_time' => '22:00',
        ]);

        $table = RestaurantTable::create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'T-1',
            'capacity' => 4,
        ]);

        $reservation = RestaurantReservation::create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $owner->guest->id,
            'guest_name' => $owner->name,
            'guest_email' => $owner->email,
            'guest_phone' => '0200000000',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
        ]);

        $this->actingAs($otherGuest)
            ->get(route('restaurant.reservations.show', $reservation))
            ->assertForbidden();
    }
}
