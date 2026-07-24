<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RestaurantTableOrderController extends Controller
{
    public function menu(RestaurantTable $table): View|RedirectResponse
    {
        if (! $table->qr_ordering_enabled) {
            return redirect()->route('restaurant.menu')->with('error', 'QR ordering is currently disabled for this table.');
        }

        if (in_array($table->status, ['maintenance', 'cleaning'], true)) {
            return redirect()->route('restaurant.menu')->with('error', 'This table is currently unavailable.');
        }

        if (($currentTableId = session('restaurant_order.table_id')) && (int) $currentTableId !== $table->id) {
            session()->forget('cart');
        }

        session([
            'restaurant_order.table_id' => $table->id,
            'restaurant_order.table_number' => $table->table_number,
            'restaurant_order.restaurant_id' => $table->restaurant_id,
            'restaurant_order.channel' => 'qr',
        ]);

        $restaurant = $table->restaurant;
        $categories = MenuCategory::query()->with(['menuItems' => fn ($query) => $query
            ->where('is_available', true)->orderBy('sort_order')])
            ->where('is_active', true)->orderBy('sort_order')->get();
        $featuredItems = MenuItem::query()->where('is_available', true)->where('is_featured', true)
            ->orderBy('sort_order')->take(6)->get();

        return view('restaurant.menu', compact('restaurant', 'categories', 'featuredItems', 'table'));
    }

    public function leaveTable(): RedirectResponse
    {
        session()->forget(['restaurant_order.table_id', 'restaurant_order.table_number', 'restaurant_order.restaurant_id', 'restaurant_order.channel', 'cart']);

        return redirect()->route('restaurant.menu')->with('success', 'Table ordering session cleared.');
    }
}
