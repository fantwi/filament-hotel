<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Promotion;
use App\Services\RestaurantCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Coordinates the restaurant cart controller HTTP workflow.
 */
class RestaurantCartController extends Controller
{
    /**
     * Handles the add HTTP action.
     */
    public function add(MenuItem $item): RedirectResponse
    {
        abort_unless($item->is_published && $item->is_available, 422, 'This menu item is unavailable.');
        $item->load('recipeIngredients.ingredient');

        if (! $item->canPrepare()) {
            return back()->with('error', 'This menu item cannot currently be prepared because one or more ingredients are out of stock.');
        }

        $cart = session('cart', []);
        $cart[$item->id]['quantity'] = min(99, ((int) ($cart[$item->id]['quantity'] ?? 0)) + 1);
        session(['cart' => $cart]);

        return back()->with('success', 'Item added to cart.');
    }

    /**
     * Displays the index interface or response.
     */
    public function index(Request $request, RestaurantCartService $cart): View
    {
        $items = $cart->items();
        $promotionCode = strtoupper(trim((string) $request->query('promotion_code', '')));
        $promotion = null;
        $promotionError = null;

        if ($promotionCode !== '') {
            $promotion = Promotion::query()
                ->where('code', $promotionCode)
                ->applicable((float) $items->sum('line_total'))
                ->first();

            if (! $promotion) {
                $promotionError = 'This discount code is not valid for the current cart.';
            }
        }

        return view('restaurant.cart', [
            'cartItems' => $items,
            'totals' => $cart->totals($promotion),
            'promotionCode' => $promotion?->code ?? $promotionCode,
            'promotionError' => $promotionError,
        ]);
    }

    /**
     * Validates and persists changes to an existing record.
     */
    public function update(Request $request, MenuItem $item): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $cart = session('cart', []);

        abort_unless(isset($cart[$item->id]), 404);

        $cart[$item->id]['quantity'] = $data['quantity'];
        session(['cart' => $cart]);

        return back()->with('success', 'Cart updated.');
    }

    /**
     * Handles the remove HTTP action.
     */
    public function remove(MenuItem $item): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$item->id]);
        session(['cart' => $cart]);

        return back()->with('success', 'Item removed from cart.');
    }
}
