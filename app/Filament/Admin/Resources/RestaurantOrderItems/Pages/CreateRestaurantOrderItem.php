<?php

namespace App\Filament\Admin\Resources\RestaurantOrderItems\Pages;

use App\Filament\Admin\Resources\RestaurantOrderItems\RestaurantOrderItemResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create restaurant order item.
 */
class CreateRestaurantOrderItem extends CreateRecord
{
    protected static string $resource = RestaurantOrderItemResource::class;
}
