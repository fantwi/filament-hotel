<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Collection;

/**
 * Encapsulates business rules for restaurant cart service.
 */
class RestaurantCartService
{
    /**
     * Returns the current restaurant cart items with their calculated line totals.
     */
    public function items(): Collection
    {
        $cart = collect(session('cart', []));
        $menuItems = MenuItem::query()->whereIn('id', $cart->keys())->where('is_available', true)->get()->keyBy('id');

        return $cart->map(function (array $line, int|string $id) use ($menuItems) {
            $item = $menuItems->get($id);
            if (! $item) return null;
            $quantity = max(1, (int) ($line['quantity'] ?? 1));
            return ['item' => $item, 'quantity' => $quantity, 'line_total' => $item->price * $quantity];
        })->filter()->values();
    }

    /**
     * Calculates cart subtotal, discount, taxes, service charge, and final total.
     */
    public function totals(?\App\Models\Promotion $promotion = null): array
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $billing = app(BillingService::class)->calculate(
            $subtotal,
            $promotion?->discount_type,
            (float) ($promotion?->discount_value ?? 0),
        );

        return [
            'subtotal' => $billing['subtotal'],
            'discount' => $billing['discount'],
            'net' => $billing['net'],
            'vat' => $billing['vat'],
            'nhil' => $billing['nhil'],
            'tax' => $billing['vat'] + $billing['nhil'],
            'service_charge' => $billing['serviceCharge'],
            'vat_rate' => $billing['vatRate'],
            'nhil_rate' => $billing['nhilRate'],
            'service_charge_rate' => $billing['serviceChargeRate'],
            'total' => $billing['total'],
        ];
    }
}
