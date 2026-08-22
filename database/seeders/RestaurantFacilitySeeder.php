<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

/**
 * Seeds initial data for restaurant facility seeder.
 */
class RestaurantFacilitySeeder extends Seeder
{
    /**
     * Performs the run data-seeding operation.
     */
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
            return Facility::updateOrCreate(['name' => $name], ['icon' => $icon, 'is_published' => true])->id;
        });

        $restaurant->facilities()->syncWithoutDetaching($ids->all());
    }
}
