<?php

namespace App\Filament\Admin\Resources\RestaurantTables\Pages;

use App\Filament\Admin\Resources\RestaurantTables\RestaurantTableResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create restaurant table.
 */
class CreateRestaurantTable extends CreateRecord
{
    protected static string $resource = RestaurantTableResource::class;
}
