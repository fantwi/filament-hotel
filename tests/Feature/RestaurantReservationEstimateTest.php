<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantReservationEstimateTest extends TestCase
{
    use RefreshDatabase;

    public function test_applying_a_valid_discount_returns_a_table_reservation_billing_preview(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'description' => 'A restaurant used for reservation estimates.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
            'capacity' => 40,
            'is_open' => true,
            'is_published' => true,
        ]);

        $table = RestaurantTable::create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'A1',
            'capacity' => 4,
            'reservation_fee' => 200,
            'status' => 'available',
        ]);

        Promotion::create([
            'name' => 'Ten percent off',
            'code' => 'TABLE10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $response = $this->from(route('restaurant.reserve'))->post(route('restaurant.reserve.store'), [
            'restaurant_table_id' => $table->id,
            'guest_name' => 'Guest Example',
            'guest_email' => 'guest@example.com',
            'guest_phone' => '0200000000',
            'reservation_date' => today()->addWeek()->toDateString(),
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'promotion_code' => 'TABLE10',
            'apply_discount' => 1,
        ]);

        $response->assertRedirect(route('restaurant.reserve'));
        $response->assertSessionHas('restaurant_reservation_billing_preview', function (array $preview): bool {
            return $preview['promotion_code'] === 'TABLE10'
                && (float) $preview['subtotal'] === 200.0
                && (float) $preview['discount'] === 20.0
                && (float) $preview['net'] === 180.0
                && (float) $preview['total'] === 210.6;
        });
        $this->assertDatabaseCount(RestaurantReservation::class, 0);
    }
}
