<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Restaurant::create([

            'name' => 'My Hotel Restaurant',

            'description' => 'Fine dining with local and international cuisine.',

            'opening_time' => '06:00',

            'closing_time' => '23:00',

            'capacity' => 120,

            'dress_code' => 'Smart Casual',

            'cuisine' => 'African & Continental',

            'is_open' => true,

        ]);
    }
}
