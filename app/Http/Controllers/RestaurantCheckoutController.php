<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\RecipeIngredient;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use App\Services\RestaurantCartService;
use App\Services\CorporateCreditService;
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
        $tableId = session('restaurant_order.table_id');
        $table = null;

        if ($tableId) {
            $table = RestaurantTable::query()
                ->whereKey($tableId)
                ->where('qr_ordering_enabled', true)
                ->whereNotIn('status', ['maintenance', 'cleaning'])
                ->first();

            if (! $table) {
                session()->forget(['restaurant_order.table_id', 'restaurant_order.table_number', 'restaurant_order.restaurant_id', 'restaurant_order.channel']);

                return redirect()->route('cart.index')
                    ->with('error', 'The selected restaurant table is no longer available for QR ordering.');
            }
        }

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
        $organization = app(CorporateCreditService::class)->organizationFor(auth()->user());

        $order = DB::transaction(function () use ($items, $totals, $data, $guest, $table, $organization) {
            $order = RestaurantOrder::create([
                'guest_id' => $guest?->id,
                'corporate_organization_id' => $organization?->id,
                'restaurant_table_id' => $table?->id,
                'ordering_channel' => $table ? session('restaurant_order.channel', 'qr') : 'web',
                'order_number' => 'FOOD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'customer_email' => $data['email'],
                'payment_method' => $organization ? 'corporate_account' : null,
                'payment_status' => 'pending',
                'status' => $organization ? 'confirmed' : 'pending',
                'confirmed_at' => $organization ? now() : null,
                ...$totals,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $line) {
                $menuItem = MenuItem::query()
                    ->with('recipeIngredients.ingredient')
                    ->published()
                    ->whereKey($line['item']->id)
                    ->where('is_available', true)
                    ->firstOrFail();
                $quantity = max(1, (int) $line['quantity']);
                $ingredientSnapshot = $menuItem->recipeIngredients
                    ->map(fn (RecipeIngredient $recipe): array => [
                        'ingredient_id' => $recipe->ingredient_id,
                        'ingredient_name' => $recipe->ingredient?->name,
                        'unit' => $recipe->ingredient?->unit,
                        'quantity_per_item' => (float) $recipe->quantity_per_item,
                        'consumption_mode' => $menuItem->inventory_consumption_mode,
                    ])
                    ->values()
                    ->all();

                $order->items()->create([
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'production_unit' => $menuItem->production_unit,
                    'production_usage_per_sale' => $menuItem->production_usage_per_sale,
                    'ingredient_usage_snapshot' => $ingredientSnapshot,
                    'quantity' => $quantity,
                    'unit_price' => $menuItem->price,
                    'total_price' => $menuItem->price * $quantity,
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
