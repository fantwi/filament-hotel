<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\View\View;

class RestaurantPageController extends Controller
{
    public function index(): View
    {
        [$restaurant, $categories, $featuredItems] = $this->menuData(4);

        $restaurant?->load([
            'tables' => fn ($query) => $query->orderBy('table_number')->limit(3),
            'facilities' => fn ($query) => $query->published(),
        ]);

        return view('restaurant.index', compact('restaurant', 'categories', 'featuredItems'));
    }

    public function tables(): View
    {
        $restaurant = Restaurant::published()->with([
            'tables' => fn ($query) => $query->orderBy('table_number'),
        ])->first();

        return view('restaurant.tables', compact('restaurant'));
    }

    public function gallery(): View
    {
        $restaurant = Restaurant::published()->first();

        return view('restaurant.gallery', compact('restaurant'));
    }

    public function menu(): View
    {
        [$restaurant, $categories, $featuredItems] = $this->menuData(4);

        return view('restaurant.menu', compact('restaurant', 'categories', 'featuredItems'));
    }

    private function menuData(int $featuredLimit): array
    {
        $restaurant = Restaurant::published()->first();
        $categories = MenuCategory::published()
            ->with(['menuItems' => fn ($query) => $query
                ->published()
                ->where('is_available', true)
                ->orderBy('sort_order')])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $featuredItems = MenuItem::published()
            ->where('is_available', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->limit($featuredLimit)
            ->get();

        return [$restaurant, $categories, $featuredItems];
    }
}
