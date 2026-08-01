<?php

namespace App\Services;

use App\Models\RestaurantOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RestaurantKitchenService
{
    public function __construct(private readonly KitchenStockService $stockService) {}

    public function confirm(RestaurantOrder $order): RestaurantOrder
    {
        $this->ensurePaymentCompleted($order);
        $this->ensureStatusIs($order, ['pending']);

        return $this->updateOrder($order, ['status' => 'confirmed', 'confirmed_at' => now()], 'confirmed', 'Restaurant food order confirmed.');
    }

    public function startPreparing(RestaurantOrder $order, ?string $kitchenNotes = null): RestaurantOrder
    {
        return DB::transaction(function () use ($order, $kitchenNotes): RestaurantOrder {
            $order = RestaurantOrder::query()->lockForUpdate()->findOrFail($order->id);
            $this->ensurePaymentCompleted($order);
            $this->ensureStatusIs($order, ['confirmed']);
            $this->stockService->consumeForOrder($order);

            $order->update([
                'status' => 'preparing',
                'preparing_at' => now(),
                'prepared_by' => auth()->id(),
                'kitchen_notes' => $kitchenNotes ?: $order->kitchen_notes,
            ]);

            activity()
                ->performedOn($order)
                ->causedBy(auth()->user())
                ->event('preparing')
                ->withProperties(['kitchen_notes' => $kitchenNotes])
                ->log('Kitchen started preparing the order and ingredient stock was deducted.');

            return $order->refresh();
        });
    }

    public function markReady(RestaurantOrder $order): RestaurantOrder
    {
        $this->ensureStatusIs($order, ['preparing']);

        return $this->updateOrder($order, ['status' => 'ready', 'ready_at' => now()], 'ready', 'Restaurant food order marked ready.');
    }

    public function markServed(RestaurantOrder $order): RestaurantOrder
    {
        $this->ensureStatusIs($order, ['ready']);

        return $this->updateOrder($order, [
            'status' => 'served',
            'served_at' => now(),
            'served_by' => auth()->id(),
        ], 'served', 'Restaurant food order served.');
    }

    public function cancel(RestaurantOrder $order, ?string $reason = null): RestaurantOrder
    {
        return DB::transaction(function () use ($order, $reason): RestaurantOrder {
            $order = RestaurantOrder::query()->lockForUpdate()->findOrFail($order->id);
            $this->ensureStatusIs($order, ['pending', 'confirmed', 'preparing']);
            $this->stockService->reverseForOrder($order);

            $notes = collect([$order->kitchen_notes, $reason ? 'Cancellation reason: '.$reason : null])
                ->filter()
                ->implode(PHP_EOL);

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'kitchen_notes' => $notes ?: null,
            ]);

            activity()
                ->performedOn($order)
                ->causedBy(auth()->user())
                ->event('cancelled')
                ->withProperties(['reason' => $reason])
                ->log('Restaurant food order cancelled.');

            return $order->refresh();
        });
    }

    private function updateOrder(RestaurantOrder $order, array $attributes, string $event, string $message, array $properties = []): RestaurantOrder
    {
        return DB::transaction(function () use ($order, $attributes, $event, $message, $properties): RestaurantOrder {
            $order->update($attributes);

            activity()
                ->performedOn($order)
                ->causedBy(auth()->user())
                ->event($event)
                ->withProperties($properties)
                ->log($message);

            return $order->refresh();
        });
    }

    private function ensurePaymentCompleted(RestaurantOrder $order): void
    {
        if ($order->payment_status !== 'completed' && $order->payment_method !== 'corporate_account') {
            throw ValidationException::withMessages([
                'payment_status' => 'The order must be paid or billed to a corporate account before it enters the kitchen queue.',
            ]);
        }
    }

    private function ensureStatusIs(RestaurantOrder $order, array $allowedStatuses): void
    {
        if (! in_array($order->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'This action is not allowed for the current order status.',
            ]);
        }
    }
}
