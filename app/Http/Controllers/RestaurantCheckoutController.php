<?php

namespace App\Http\Controllers;

use App\Models\RestaurantOrder;
use App\Services\RestaurantCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RestaurantCheckoutController extends Controller
{
    public function index(RestaurantCartService $cart): View|RedirectResponse
    {
        if ($cart->items()->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        return view('restaurant.checkout', [
            'cartItems' => $cart->items(),
            'totals' => $cart->totals(),
        ]);
    }

    public function store(Request $request, RestaurantCartService $cart): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $items = $cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $totals = $cart->totals();
        $guest = auth()->user()?->guest;

        $order = DB::transaction(function () use ($items, $totals, $data, $guest) {
            $order = RestaurantOrder::create([
                'guest_id' => $guest?->id,
                'order_number' => 'FOOD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'customer_email' => $data['email'],
                ...$totals,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $line) {
                $order->items()->create([
                    'menu_item_id' => $line['item']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['item']->price,
                    'total_price' => $line['line_total'],
                ]);
            }

            return $order;
        });

        session()->forget('cart');
        session()->push('restaurant_order_ids', $order->id);

        return redirect()->route('restaurant.orders.confirmation', $order)
            ->with('success', "Order {$order->order_number} has been received. Complete payment to send it to the kitchen.");
    }
}
