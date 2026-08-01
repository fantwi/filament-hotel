<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;

class RestaurantTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurant = Restaurant::where('name', 'My Hotel Restaurant')->firstOrFail();

        foreach ([
            ['table_number' => 'T1', 'capacity' => 2, 'location' => 'Indoor'],
            ['table_number' => 'T2', 'capacity' => 4, 'location' => 'Indoor'],
            ['table_number' => 'VIP-1', 'capacity' => 8, 'location' => 'VIP Lounge'],
            ['table_number' => 'Terrace-1', 'capacity' => 6, 'location' => 'Outdoor'],
        ] as $table) {
            RestaurantTable::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'table_number' => $table['table_number']],
                [...$table, 'status' => 'available'],
            );
        }
    }
}
