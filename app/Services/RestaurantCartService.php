<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Collection;

class RestaurantCartService
{
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

    public function totals(): array
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $billing = app(BillingService::class)->calculate($subtotal);

        return [
            'subtotal' => $billing['subtotal'],
            'tax' => $billing['vat'] + $billing['nhil'],
            'service_charge' => $billing['serviceCharge'],
            'total' => $billing['total'],
        ];
    }
}
