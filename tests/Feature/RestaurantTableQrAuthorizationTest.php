<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTableQrAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_print_restaurant_table_qr_codes(): void
    {
        $table = $this->createRestaurantTable();
        $guest = User::factory()->create(['department' => 'guest']);

        $this->actingAs($guest)
            ->get(route('restaurant.tables.qr.print', $table))
            ->assertForbidden();
    }

    public function test_managers_can_print_restaurant_table_qr_codes(): void
    {
        $table = $this->createRestaurantTable();
        $manager = User::factory()->create(['department' => 'management']);

        $this->actingAs($manager)
            ->get(route('restaurant.tables.qr.print', $table))
            ->assertOk();
    }

    private function createRestaurantTable(): RestaurantTable
    {
        $restaurant = Restaurant::create([
            'name' => 'Security Test Restaurant',
            'description' => 'Test restaurant.',
            'opening_time' => '09:00',
            'closing_time' => '22:00',
            'capacity' => 20,
        ]);

        return RestaurantTable::create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'A1',
            'capacity' => 4,
        ]);
    }
}
