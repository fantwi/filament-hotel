<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\RestaurantOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RestaurantOrderPaymentController extends Controller
{
    public function confirmation(RestaurantOrder $order): View
    {
        $this->authorizeOrder($order);

        return view('restaurant.order-confirmation', compact('order'));
    }

    public function initialize(RestaurantOrder $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->payment_status === 'completed') {
            return redirect()->route('restaurant.orders.confirmation', $order)
                ->with('success', 'This order has already been paid.');
        }

        if (! $order->customer_email) {
            return redirect()->route('restaurant.checkout')
                ->with('error', 'An email address is required to make payment.');
        }

        $reference = 'FOOD-'.Str::upper(Str::random(16));
        $order->update(['transaction_reference' => $reference]);

        $response = Http::timeout(15)
            ->withToken(config('services.paystack.secretKey'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $order->customer_email,
                'amount' => (int) round($order->total * 100),
                'reference' => $reference,
                'callback_url' => route('restaurant.orders.payment.callback'),
                'metadata' => ['restaurant_order_id' => $order->id],
            ]);

        $data = $response->json();

        if (! $response->successful() || ! isset($data['data']['authorization_url'])) {
            return back()->with('error', 'Unable to initialize payment. Please try again.');
        }

        return redirect()->away($data['data']['authorization_url']);
    }

    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->string('reference')->toString();
        $order = RestaurantOrder::where('transaction_reference', $reference)->firstOrFail();

        $response = Http::timeout(15)
            ->withToken(config('services.paystack.secretKey'))
            ->get("https://api.paystack.co/transaction/verify/{$reference}");
        $data = $response->json('data');

        $isValid = $response->successful()
            && ($data['status'] ?? null) === 'success'
            && ($data['reference'] ?? null) === $reference
            && (int) ($data['amount'] ?? 0) === (int) round($order->total * 100)
            && (int) data_get($data, 'metadata.restaurant_order_id') === $order->id;

        if (! $isValid) {
            return redirect()->route('restaurant.orders.confirmation', $order)
                ->with('error', 'Payment verification failed. No payment was recorded.');
        }

        DB::transaction(function () use ($order, $reference): void {
            $order = RestaurantOrder::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->payment_status === 'completed') {
                return;
            }

            $order->update([
                'payment_status' => 'completed',
                'status' => 'confirmed',
                'payment_method' => 'paystack',
                'paid_at' => now(),
            ]);

            Payment::firstOrCreate(
                ['transaction_reference' => $reference],
                [
                    'restaurant_order_id' => $order->id,
                    'guest_id' => $order->guest_id,
                    'amount' => $order->total,
                    'method' => 'paystack',
                    'payment_status' => 'completed',
                ]
            );
        });

        return redirect()->route('restaurant.orders.confirmation', $order)
            ->with('success', 'Payment received. Your order has been sent to the kitchen.');
    }

    private function authorizeOrder(RestaurantOrder $order): void
    {
        $sessionOrders = session('restaurant_order_ids', []);
        $ownsSessionOrder = in_array($order->id, $sessionOrders, true);
        $ownsGuestOrder = auth()->id() && $order->guest?->user_id === auth()->id();

        abort_unless($ownsSessionOrder || $ownsGuestOrder, 403);
    }
}
