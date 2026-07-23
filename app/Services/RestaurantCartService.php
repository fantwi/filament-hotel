<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Collection;

class RestaurantCartService
{
    public function items(): Collection
    {
        $cart = collect(session('cart', []));
        $menuItems = MenuItem::query()
            ->whereIn('id', $cart->keys())
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        return $cart
            ->map(function (array $line, int|string $id) use ($menuItems) {
                $item = $menuItems->get($id);

                if (! $item) {
                    return null;
                }

                $quantity = max(1, (int) ($line['quantity'] ?? 1));

                return [
                    'item' => $item,
                    'quantity' => $quantity,
                    'line_total' => $item->price * $quantity,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return array{subtotal: float, tax: float, service_charge: float, total: float}
     */
    public function totals(): array
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $tax = 0.0;
        $serviceCharge = 0.0;

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'service_charge' => $serviceCharge,
            'total' => $subtotal + $tax + $serviceCharge,
        ];
    }
}
