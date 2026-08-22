<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

/**
 * Coordinates the restaurant order payment controller HTTP workflow.
 */
class RestaurantOrderPaymentController extends Controller
{
    /**
     * Displays the current order and payment confirmation state.
     */
    public function confirmation(RestaurantOrder $order): View
    {
        $this->authorizeOrder($order);

        return view('restaurant.order-confirmation', compact('order'));
    }

    /**
     * Starts the Paystack payment flow for the requested food order.
     */
    public function initialize(RestaurantOrder $order): RedirectResponse
    {
        $this->authorizeOrder($order);


        if ($order->payment_method === 'corporate_account' || $order->corporate_organization_id) {
            return redirect()->route('restaurant.orders.confirmation', $order)
                ->with('error', 'This order is billed to your corporate account and cannot be paid through Paystack.');
        }

        if ($order->payment_status === 'completed') {
            return redirect()->route('restaurant.orders.confirmation', $order)
                ->with('success', 'This order has already been paid.');
        }

        if (! $order->customer_email) {
            return redirect()->route('restaurant.checkout')
                ->with('error', 'An email address is required to make payment.');
        }

        $reference = 'FOOD-'.Str::upper(Str::random(16));
        $this->recordPaymentAttempt($order, $reference);

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

    /**
     * Processes the browser return from Paystack after food-order payment.
     */
    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->string('reference')->toString();
        $order = $this->orderForReference($reference);
        abort_unless($order, 404);

        $response = Http::timeout(15)
            ->withToken(config('services.paystack.secretKey'))
            ->get("https://api.paystack.co/transaction/verify/{$reference}");
        $data = $response->json('data');

        if (! $this->isValidPayment($data, $order, $reference)) {
            return redirect()->route('restaurant.orders.confirmation', $order)
                ->with('error', 'Payment verification failed. No payment was recorded.');
        }

        $paidOrder = $this->recordPayment($order, $reference);

        if ($paidOrder) {
            $this->notifyKitchen($paidOrder);
        }

        return redirect()->route('restaurant.orders.confirmation', $order)
            ->with('success', 'Payment received. Your order has been sent to the kitchen.');
    }

    /**
     * Processes verified Paystack server-to-server food-order payment notifications.
     */
    public function webhook(Request $request): JsonResponse
    {
        $secret = (string) config('services.paystack.secretKey');
        $signature = (string) $request->header('x-paystack-signature', '');

        abort_if(
            $secret === ''
                || $signature === ''
                || ! hash_equals(hash_hmac('sha512', $request->getContent(), $secret), $signature),
            403,
        );

        $payload = $request->json()->all();

        if (($payload['event'] ?? null) !== 'charge.success') {
            return response()->json(['status' => true]);
        }

        $data = $payload['data'] ?? [];
        $reference = (string) ($data['reference'] ?? '');
        $order = $this->orderForReference($reference);

        if (! $order || ! $this->isValidPayment($data, $order, $reference)) {
            return response()->json(['status' => false], 422);
        }

        $paidOrder = $this->recordPayment($order, $reference);

        if ($paidOrder) {
            $this->notifyKitchen($paidOrder);
        }

        return response()->json(['status' => true]);
    }

    /**
     * Records  payment attempt for audit and reporting.
     */
    private function recordPaymentAttempt(RestaurantOrder $order, string $reference): void
    {
        DB::transaction(function () use ($order, $reference): void {
            $order = RestaurantOrder::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->payment_status === 'completed') {
                return;
            }

            Payment::firstOrCreate(
                ['transaction_reference' => $reference],
                [
                    'restaurant_order_id' => $order->id,
                    'guest_id' => $order->guest_id,
                    'amount' => $order->total,
                    'method' => 'paystack',
                    'payment_status' => 'pending',
                ],
            );

            $order->update(['transaction_reference' => $reference]);
        });
    }

    /**
     * Resolves a food order from its payment reference with the required relationships.
     */
    private function orderForReference(string $reference): ?RestaurantOrder
    {
        if ($reference === '') {
            return null;
        }

        $attempt = Payment::query()
            ->with('restaurantOrder')
            ->where('transaction_reference', $reference)
            ->whereNotNull('restaurant_order_id')
            ->first();

        return $attempt?->restaurantOrder
            ?? RestaurantOrder::where('transaction_reference', $reference)->first();
    }

    /**
     * Determines whether this record is valid payment.
     */
    private function isValidPayment(?array $data, RestaurantOrder $order, string $reference): bool
    {
        return is_array($data)
            && ($data['status'] ?? null) === 'success'
            && ($data['reference'] ?? null) === $reference
            && (int) ($data['amount'] ?? 0) === (int) round($order->total * 100)
            && (int) data_get($data, 'metadata.restaurant_order_id') === $order->id;
    }

    /**
     * Records  payment for audit and reporting.
     */
    private function recordPayment(RestaurantOrder $order, string $reference): ?RestaurantOrder
    {
        return DB::transaction(function () use ($order, $reference): ?RestaurantOrder {
            $order = RestaurantOrder::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            $payment = Payment::query()
                ->where('transaction_reference', $reference)
                ->lockForUpdate()
                ->first();

            if ($payment && $payment->restaurant_order_id !== $order->id) {
                return null;
            }

            $payment ??= Payment::create([
                'transaction_reference' => $reference,
                'restaurant_order_id' => $order->id,
                'guest_id' => $order->guest_id,
                'amount' => $order->total,
                'method' => 'paystack',
                'payment_status' => 'pending',
            ]);

            $payment->update([
                'restaurant_order_id' => $order->id,
                'guest_id' => $order->guest_id,
                'amount' => $order->total,
                'method' => 'paystack',
                'payment_status' => 'completed',
            ]);

            if ($order->payment_status === 'completed') {
                return null;
            }

            $order->update([
                'transaction_reference' => $reference,
                'payment_status' => 'completed',
                'status' => 'confirmed',
                'payment_method' => 'paystack',
                'paid_at' => now(),
                'confirmed_at' => now(),
            ]);

            return $order->refresh();
        });
    }

    /**
     * Sends a database notification to staff responsible for kitchen fulfilment.
     */
    private function notifyKitchen(RestaurantOrder $order): void
    {
        if (! Permission::query()
            ->where('name', 'manage kitchen orders')
            ->where('guard_name', 'web')
            ->exists()) {
            return;
        }

        User::permission('manage kitchen orders')->each(function (User $kitchenUser) use ($order): void {
            Notification::make()
                ->title('New paid restaurant order')
                ->body("Order {$order->order_number} is ready for kitchen preparation.")
                ->icon('heroicon-o-shopping-bag')
                ->success()
                ->sendToDatabase($kitchenUser);
        });
    }

    /**
     * Confirms that the current guest may access the requested food order.
     */
    private function authorizeOrder(RestaurantOrder $order): void
    {
        $sessionOrders = collect(session('restaurant_order_ids', []))
            ->map(fn ($id): int => (int) $id);
        $ownsSessionOrder = $sessionOrders->contains((int) $order->getKey());
        // MySQL/MariaDB may hydrate foreign keys as strings while the authenticated
        // user ID is an integer. Compare normalized primary keys to avoid denying
        // a guest access to their own newly-created order.
        $authenticatedGuestId = auth()->user()?->guest?->getKey();
        $ownsGuestOrder = $authenticatedGuestId !== null
            && (int) $order->guest_id === (int) $authenticatedGuestId;

        abort_unless($ownsSessionOrder || $ownsGuestOrder, 403);
    }
}
