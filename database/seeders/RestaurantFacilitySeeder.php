<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RestaurantFacilitySeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Restaurant::first();

        if (! $restaurant) {
            return;
        }

        $facilities = [
            'Bar' => '🍷',
            'Free WiFi' => '📶',
            'Live Music' => '🎵',
            'Air Conditioned' => '❄️',
            'Family Friendly' => '👪',
            'Parking' => '🚗',
        ];

        $ids = collect($facilities)->map(function (string $icon, string $name): int {
            return Facility::firstOrCreate(['name' => $name], ['icon' => $icon])->id;
        });

        $restaurant->facilities()->syncWithoutDetaching($ids->all());
    }
}
