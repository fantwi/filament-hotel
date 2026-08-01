<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['Breakfast', 'Pancakes', 'Fluffy pancakes served with seasonal fruit.', 45, 15, false],
            ['Breakfast', 'Breakfast Coffee', 'Freshly brewed coffee.', 20, 5, false],
            ['Lunch', 'Jollof Rice', 'Ghanaian jollof rice with grilled chicken.', 95, 30, true],
            ['Lunch', 'Fried Rice', 'Vegetable fried rice with your choice of protein.', 90, 25, false],
            ['Dinner', 'Grilled Chicken', 'Herb-marinated chicken with roasted vegetables.', 120, 35, true],
            ['Appetizers', 'Caesar Salad', 'Crisp romaine, parmesan, croutons, and dressing.', 65, 15, true],
            ['Main Courses', 'Banku and Tilapia', 'Grilled tilapia served with banku and pepper sauce.', 130, 40, false],
            ['Desserts', 'Chocolate Cake', 'Rich chocolate cake with vanilla cream.', 50, 10, true],
            ['Drinks', 'Fresh Orange Juice', 'Freshly squeezed orange juice.', 30, 5, false],
            ['Cocktails', 'Tropical Sunset', 'A refreshing tropical fruit cocktail.', 55, 5, false],
            ['Wines', 'House Red Wine', 'A glass of our selected house red.', 70, 5, false],
            ['Kids Menu', 'Mini Chicken Bites', 'Chicken bites with fries and fruit.', 55, 20, false],
        ];

        foreach ($items as [$categoryName, $name, $description, $price, $preparationTime, $featured]) {
            $category = MenuCategory::where('name', $categoryName)->firstOrFail();

            MenuItem::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'menu_category_id' => $category->id,
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'is_available' => true,
                    'is_featured' => $featured,
                    'is_published' => true,
                    'preparation_time' => $preparationTime,
                    'sort_order' => 0,
                ]
            );
        }
    }
}
