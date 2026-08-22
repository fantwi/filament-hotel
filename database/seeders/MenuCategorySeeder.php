<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds initial data for menu category seeder.
 */
class MenuCategorySeeder extends Seeder
{
    /**
     * Performs the run data-seeding operation.
     */
    public function run(): void
    {
        $categories = [
            'Breakfast',
            'Lunch',
            'Dinner',
            'Appetizers',
            'Main Courses',
            'Desserts',
            'Drinks',
            'Cocktails',
            'Wines',
            'Kids Menu',
        ];

        foreach ($categories as $index => $name) {
            MenuCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'is_published' => true,
                ]
            );
        }
    }
}
