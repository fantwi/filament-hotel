<?php

namespace App\Filament\Admin\Resources\Restaurants\Pages;

use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create restaurant.
 */
class CreateRestaurant extends CreateRecord
{
    protected static string $resource = RestaurantResource::class;
}
