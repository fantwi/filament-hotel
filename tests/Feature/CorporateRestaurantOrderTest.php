<?php

namespace Tests\Feature;

use App\Models\RestaurantOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorporateRestaurantOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_corporate_orders_are_included_in_the_kitchen_queue_without_being_marked_paid(): void
    {
        $corporateOrder = $this->order([
            'order_number' => 'FOOD-CORPORATE-QUEUE',
            'payment_method' => 'corporate_account',
            'payment_status' => 'pending',
            'status' => 'confirmed',
        ]);
        $unpaidOrder = $this->order([
            'order_number' => 'FOOD-UNPAID-QUEUE',
            'payment_status' => 'pending',
            'status' => 'confirmed',
        ]);

        self::assertTrue($corporateOrder->isKitchenEligible());
        self::assertFalse($unpaidOrder->isKitchenEligible());
        self::assertSame(
            [$corporateOrder->id],
            RestaurantOrder::kitchenQueue()->pluck('id')->all(),
        );
    }

    public function test_corporate_order_confirmation_identifies_an_unpaid_charge_without_offering_paystack_payment(): void
    {
        $order = $this->order([
            'order_number' => 'FOOD-CORPORATE-VIEW',
            'payment_method' => 'corporate_account',
            'payment_status' => 'pending',
            'status' => 'confirmed',
        ]);

        $this->view('restaurant.order-confirmation', compact('order'))
            ->assertSee('Billing: <strong>Corporate account</strong>', false)
            ->assertSee('awaiting settlement')
            ->assertDontSee('Pay securely with Paystack');
    }

    public function test_corporate_order_paystack_endpoint_returns_to_confirmation_without_initializing_payment(): void
    {
        $order = $this->order([
            'order_number' => 'FOOD-CORPORATE-NO-PAYSTACK',
            'payment_method' => 'corporate_account',
            'payment_status' => 'pending',
            'status' => 'confirmed',
        ]);

        $this->withSession(['restaurant_order_ids' => [$order->id]])
            ->post(route('restaurant.orders.pay', $order))
            ->assertRedirect(route('restaurant.orders.confirmation', $order))
            ->assertSessionHas('error', 'This order is billed to your corporate account and cannot be paid through Paystack.');
    }

    private function order(array $attributes): RestaurantOrder
    {
        return RestaurantOrder::create([
            'customer_email' => 'corporate@example.test',
            'subtotal' => 100,
            'discount' => 0,
            'vat' => 12.5,
            'nhil' => 2.5,
            'tax' => 15,
            'service_charge' => 2,
            'total' => 117,
            'ordering_channel' => 'web',
            ...$attributes,
        ]);
    }
}
