<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\RestaurantOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function sendWebhook(RestaurantOrder $order)
    {
        $payload = $this->payloadFor($order);
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

    private function payloadFor(RestaurantOrder $order): array
    {
        return [
            'event' => 'charge.success',
            'data' => [
                'status' => 'success',
                'reference' => $order->transaction_reference,
                'amount' => (int) round($order->total * 100),
                'metadata' => ['restaurant_order_id' => $order->id],
            ],
        ];
    }
}
