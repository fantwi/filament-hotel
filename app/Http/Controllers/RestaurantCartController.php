<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Services\RestaurantCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantCartController extends Controller
{
    public function add(MenuItem $item): RedirectResponse
    {
        abort_unless($item->is_published && $item->is_available, 422, 'This menu item is unavailable.');

        $cart = session('cart', []);
        $cart[$item->id]['quantity'] = min(99, ((int) ($cart[$item->id]['quantity'] ?? 0)) + 1);
        session(['cart' => $cart]);

        return back()->with('success', 'Item added to cart.');
    }

    public function index(RestaurantCartService $cart): View
    {
        return view('restaurant.cart', [
            'cartItems' => $cart->items(),
            'totals' => $cart->totals(),
        ]);
    }

    public function update(Request $request, MenuItem $item): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $cart = session('cart', []);

        abort_unless(isset($cart[$item->id]), 404);

        $cart[$item->id]['quantity'] = $data['quantity'];
        session(['cart' => $cart]);

        return back()->with('success', 'Cart updated.');
    }

    public function remove(MenuItem $item): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$item->id]);
        session(['cart' => $cart]);

        return back()->with('success', 'Item removed from cart.');
    }
}
