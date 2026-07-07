<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestaurantTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        RestaurantTable::insert([

            [
                'restaurant_id' => 1,
                'table_number' => 'T1',
                'capacity' => 2,
                'location' => 'Indoor',
                'status' => 'available',
            ],

            [
                'restaurant_id' => 1,
                'table_number' => 'T2',
                'capacity' => 4,
                'location' => 'Indoor',
                'status' => 'available',
            ],

            [
                'restaurant_id' => 1,
                'table_number' => 'VIP-1',
                'capacity' => 8,
                'location' => 'VIP Lounge',
                'status' => 'available',
            ],

            [
                'restaurant_id' => 1,
                'table_number' => 'Terrace-1',
                'capacity' => 6,
                'location' => 'Outdoor',
                'status' => 'available',
            ],
        ]);
    }
}
