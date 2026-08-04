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

        if ($order->payment_method === 'corporate_account') {
            return redirect()->route('restaurant.orders.confirmation', $order)
                ->with('success', 'This order is billed to the corporate account and is ready for the kitchen.');
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
        $order = RestaurantOrder::where('transaction_reference', $reference)->first();

        if (! $order || ! $this->isValidPayment($data, $order, $reference)) {
            return response()->json(['status' => false], 422);
        }

        $paidOrder = $this->recordPayment($order, $reference);

        if ($paidOrder) {
            $this->notifyKitchen($paidOrder);
        }

        return response()->json(['status' => true]);
    }

    private function isValidPayment(?array $data, RestaurantOrder $order, string $reference): bool
    {
        return is_array($data)
            && ($data['status'] ?? null) === 'success'
            && ($data['reference'] ?? null) === $reference
            && (int) ($data['amount'] ?? 0) === (int) round($order->total * 100)
            && (int) data_get($data, 'metadata.restaurant_order_id') === $order->id;
    }

    private function recordPayment(RestaurantOrder $order, string $reference): ?RestaurantOrder
    {
        return DB::transaction(function () use ($order, $reference): ?RestaurantOrder {
            $order = RestaurantOrder::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->payment_status === 'completed') {
                return null;
            }

            $order->update([
                'payment_status' => 'completed',
                'status' => 'confirmed',
                'payment_method' => 'paystack',
                'paid_at' => now(),
                'confirmed_at' => now(),
            ]);

            Payment::firstOrCreate(
                ['transaction_reference' => $reference],
                [
                    'restaurant_order_id' => $order->id,
                    'guest_id' => $order->guest_id,
                    'amount' => $order->total,
                    'method' => 'paystack',
                    'payment_status' => 'completed',
                ],
            );

            return $order->refresh();
        });
    }

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

    private function authorizeOrder(RestaurantOrder $order): void
    {
        $sessionOrders = session('restaurant_order_ids', []);
        $ownsSessionOrder = in_array($order->id, $sessionOrders, true);
        $ownsGuestOrder = auth()->id() && $order->guest?->user_id === auth()->id();

        abort_unless($ownsSessionOrder || $ownsGuestOrder, 403);
    }
}
