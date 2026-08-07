<?php

namespace Tests\Feature;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantCheckoutConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_recovers_a_missing_guest_record_and_allows_the_confirmation_page(): void
    {
        $user = User::factory()->create(['department' => 'guest']);
        $user->guest()->delete();
        $user->unsetRelation('guest');

        $category = MenuCategory::create([
            'name' => 'Checkout Meals',
            'slug' => 'checkout-meals',
            'is_active' => true,
        ]);
        $item = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Checkout Meal',
            'slug' => 'checkout-meal',
            'price' => 40,
            'is_available' => true,
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['cart' => [$item->id => ['quantity' => 1]]])
            ->post(route('restaurant.checkout.store'), [
                'email' => $user->email,
                'use_corporate_credit' => 0,
            ]);

        $order = RestaurantOrder::firstOrFail();

        $response->assertRedirect(route('restaurant.orders.confirmation', $order));
        self::assertNotNull($order->guest_id);

        $this->actingAs($user)
            ->withSession(['restaurant_order_ids' => [(string) $order->id]])
            ->get(route('restaurant.orders.confirmation', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }
}
