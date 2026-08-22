<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\RecipeIngredient;
use App\Models\RestaurantOrder;
use App\Models\Promotion;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\RestaurantCartService;
use App\Services\CorporateCreditService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

/**
 * Coordinates the restaurant checkout controller HTTP workflow.
 */
class RestaurantCheckoutController extends Controller
{
    /**
     * Displays the index interface or response.
     */
    public function index(Request $request, RestaurantCartService $cart): View|RedirectResponse
    {
        $items = $cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $promotionCode = strtoupper(trim((string) $request->query('promotion_code', '')));
        $promotion = null;
        $promotionError = null;

        if ($promotionCode !== '') {
            $promotion = Promotion::query()
                ->where('code', $promotionCode)
                ->applicable((float) $items->sum('line_total'))
                ->first();

            if (! $promotion) {
                $promotionError = 'This discount code is not valid for this order.';
            }
        }

        return view('restaurant.checkout', [
            'cartItems' => $items,
            'totals' => $cart->totals($promotion),
            'promotionCode' => $promotion?->code ?? $promotionCode,
            'promotionError' => $promotionError,
            'corporateOrganization' => app(CorporateCreditService::class)->organizationFor(auth()->user()),
        ]);
    }

    /**
     * Validates and persists a newly submitted record.
     */
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
            'promotion_code' => ['nullable', 'string', 'max:100'],
            'use_corporate_credit' => ['nullable', 'boolean'],
        ]);
        $items = $cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $promotion = filled($data['promotion_code'] ?? null) ? Promotion::query()->where('code', strtoupper($data['promotion_code']))->applicable((float) $items->sum('line_total'))->first() : null;
        if (filled($data['promotion_code'] ?? null) && ! $promotion) return back()->withInput()->withErrors(['promotion_code' => 'This promotion code is not valid for this order.']);
        $totals = $cart->totals($promotion);
        $user = auth()->user();
        $guest = $user?->guest;

        if ($user && ! $guest) {
            $guest = Guest::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $user->first_name ?? 'Guest',
                    'last_name' => $user->last_name ?? '',
                    'email' => $user->email,
                    'phone_number' => $user->phone_number ?? '',
                    'id_number' => $user->id_number ?? '',
                ],
            );
        }

        $eligibleOrganization = app(CorporateCreditService::class)->organizationFor(auth()->user());
        $organization = $request->boolean('use_corporate_credit') ? $eligibleOrganization : null;

        if ($request->boolean('use_corporate_credit') && ! $organization) {
            return back()->withInput()->withErrors([
                'use_corporate_credit' => 'An enabled corporate account is required for deferred payment.',
            ]);
        }

        if ($organization && ! app(CorporateCreditService::class)->canCharge($organization, (float) $totals['total'])) {
            return back()->withInput()->withErrors([
                'email' => "This order would exceed your organization credit limit.",
            ]);
        }

        $order = DB::transaction(function () use ($items, $totals, $data, $guest, $table, $organization, $promotion) {
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
                'promotion_code' => $promotion?->code,
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
        session([
            'restaurant_order_ids' => collect(session('restaurant_order_ids', []))
                ->map(fn ($id): int => (int) $id)
                ->push($order->id)
                ->unique()
                ->values()
                ->all(),
        ]);

        if ($order->isKitchenEligible()) {
            $this->notifyKitchen($order);
        }

        $message = $organization
            ? "Order {$order->order_number} has been billed to {$organization->name} and sent to the kitchen."
            : "Order {$order->order_number} has been received. Complete payment to send it to the kitchen.";

        return redirect()->route('restaurant.orders.confirmation', $order)
            ->with('success', $message);
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
                ->title('New corporate restaurant order')
                ->body("Order {$order->order_number} is billed to a corporate account and ready for kitchen preparation.")
                ->icon('heroicon-o-shopping-bag')
                ->success()
                ->sendToDatabase($kitchenUser);
        });
    }
}
