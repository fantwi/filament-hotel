<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\RestaurantOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RestaurantOrderPaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_paystack_webhook_marks_a_food_order_as_paid(): void
    {
        config(['services.paystack.secretKey' => 'webhook-secret']);

        $order = $this->orderWithReference('FOOD-WEBHOOK-1');
        $response = $this->sendWebhook($order);

        $response->assertOk()->assertJson(['status' => true]);
        $order->refresh();

        self::assertSame('completed', $order->payment_status);
        self::assertSame('confirmed', $order->status);
        self::assertSame('paystack', $order->payment_method);
        self::assertNotNull($order->paid_at);
        $this->assertDatabaseHas('payments', [
            'restaurant_order_id' => $order->id,
            'transaction_reference' => $order->transaction_reference,
            'amount' => '123.45',
            'method' => 'paystack',
            'payment_status' => 'completed',
        ]);
    }

    public function test_an_invalid_webhook_signature_cannot_change_an_order(): void
    {
        config(['services.paystack.secretKey' => 'webhook-secret']);

        $order = $this->orderWithReference('FOOD-WEBHOOK-2');
        $payload = $this->payloadFor($order);

        $response = $this->call(
            'POST',
            route('restaurant.orders.payment.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => 'invalid',
            ],
            json_encode($payload),
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('restaurant_orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
        self::assertSame(0, Payment::count());
    }

    public function test_duplicate_webhooks_create_only_one_payment_record(): void
    {
        config(['services.paystack.secretKey' => 'webhook-secret']);

        $order = $this->orderWithReference('FOOD-WEBHOOK-3');

        $this->sendWebhook($order)->assertOk();
        $this->sendWebhook($order)->assertOk();

        self::assertSame(1, Payment::count());
    }

    public function test_browser_callback_records_a_verified_paystack_payment(): void
    {
        $order = $this->orderWithReference('FOOD-CALLBACK-SUCCESS');

        Http::fake([
            'https://api.paystack.co/transaction/verify/FOOD-CALLBACK-SUCCESS' => Http::response([
                'data' => $this->paystackPaymentData($order),
            ]),
        ]);

        $response = $this->get(route('restaurant.orders.payment.callback', ['reference' => $order->transaction_reference]));

        $response->assertRedirect(route('restaurant.orders.confirmation', $order));
        $order->refresh();

        self::assertSame('completed', $order->payment_status);
        self::assertSame('confirmed', $order->status);
        $this->assertDatabaseHas('payments', [
            'restaurant_order_id' => $order->id,
            'transaction_reference' => $order->transaction_reference,
            'payment_status' => 'completed',
        ]);
    }

    public function test_browser_callback_keeps_an_order_pending_when_paystack_verification_fails(): void
    {
        $order = $this->orderWithReference('FOOD-CALLBACK-FAILED');

        Http::fake([
            'https://api.paystack.co/transaction/verify/FOOD-CALLBACK-FAILED' => Http::response([
                'data' => $this->paystackPaymentData($order, 'failed'),
            ]),
        ]);

        $response = $this->get(route('restaurant.orders.payment.callback', ['reference' => $order->transaction_reference]));

        $response
            ->assertRedirect(route('restaurant.orders.confirmation', $order))
            ->assertSessionHas('error', 'Payment verification failed. No payment was recorded.');
        $this->assertDatabaseHas('restaurant_orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
        self::assertSame(0, Payment::count());
    }

    public function test_repeated_browser_callbacks_are_idempotent(): void
    {
        $order = $this->orderWithReference('FOOD-CALLBACK-DUPLICATE');

        Http::fake([
            'https://api.paystack.co/transaction/verify/FOOD-CALLBACK-DUPLICATE' => Http::response([
                'data' => $this->paystackPaymentData($order),
            ]),
        ]);

        $this->get(route('restaurant.orders.payment.callback', ['reference' => $order->transaction_reference]))
            ->assertRedirect(route('restaurant.orders.confirmation', $order));
        $this->get(route('restaurant.orders.payment.callback', ['reference' => $order->transaction_reference]))
            ->assertRedirect(route('restaurant.orders.confirmation', $order));

        self::assertSame(1, Payment::count());
        $this->assertDatabaseHas('restaurant_orders', [
            'id' => $order->id,
            'payment_status' => 'completed',
        ]);
    }

    public function test_payment_initialization_persists_a_pending_attempt_before_redirecting(): void
    {
        $order = $this->orderWithReference('FOOD-WEBHOOK-INIT');

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'data' => ['authorization_url' => 'https://checkout.paystack.test/food-order'],
            ]),
        ]);

        $response = $this->withSession(['restaurant_order_ids' => [$order->id]])
            ->post(route('restaurant.orders.pay', $order));

        $response->assertRedirect('https://checkout.paystack.test/food-order');
        $order->refresh();

        self::assertNotSame('FOOD-WEBHOOK-INIT', $order->transaction_reference);
        $this->assertDatabaseHas('payments', [
            'restaurant_order_id' => $order->id,
            'transaction_reference' => $order->transaction_reference,
            'payment_status' => 'pending',
        ]);
    }

    public function test_a_payment_attempt_remains_payable_after_a_later_attempt_is_started(): void
    {
        config(['services.paystack.secretKey' => 'webhook-secret']);

        $order = $this->orderWithReference('FOOD-WEBHOOK-LATEST');
        $earlierReference = 'FOOD-WEBHOOK-EARLIER';

        Payment::create([
            'restaurant_order_id' => $order->id,
            'transaction_reference' => $earlierReference,
            'amount' => $order->total,
            'method' => 'paystack',
            'payment_status' => 'pending',
        ]);

        $this->sendWebhook($order, $earlierReference)->assertOk();

        $order->refresh();

        self::assertSame('completed', $order->payment_status);
        self::assertSame($earlierReference, $order->transaction_reference);
        $this->assertDatabaseHas('payments', [
            'restaurant_order_id' => $order->id,
            'transaction_reference' => $earlierReference,
            'payment_status' => 'completed',
        ]);
    }

    private function orderWithReference(string $reference): RestaurantOrder
    {
        return RestaurantOrder::create([
            'order_number' => $reference,
            'customer_email' => 'guest@example.test',
            'transaction_reference' => $reference,
            'subtotal' => 100,
            'discount' => 0,
            'vat' => 12.5,
            'nhil' => 2.5,
            'tax' => 15,
            'service_charge' => 8.45,
            'total' => 123.45,
            'status' => 'pending',
            'payment_status' => 'pending',
            'ordering_channel' => 'web',
        ]);
    }

    private function sendWebhook(RestaurantOrder $order, ?string $reference = null)
    {
        $payload = $this->payloadFor($order, $reference);
        $body = json_encode($payload);

        return $this->call(
            'POST',
            route('restaurant.orders.payment.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'webhook-secret'),
            ],
            $body,
        );
    }

    private function paystackPaymentData(RestaurantOrder $order, string $status = 'success'): array
    {
        return [
            'status' => $status,
            'reference' => $order->transaction_reference,
            'amount' => (int) round($order->total * 100),
            'metadata' => ['restaurant_order_id' => $order->id],
        ];
    }

    private function payloadFor(RestaurantOrder $order, ?string $reference = null): array
    {
        return [
            'event' => 'charge.success',
            'data' => [
                'status' => 'success',
                'reference' => $reference ?? $order->transaction_reference,
                'amount' => (int) round($order->total * 100),
                'metadata' => ['restaurant_order_id' => $order->id],
            ],
        ];
    }
}
