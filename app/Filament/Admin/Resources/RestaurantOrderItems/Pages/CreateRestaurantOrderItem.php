<?php

namespace App\Filament\Admin\Resources\RestaurantOrderItems\Pages;

use App\Filament\Admin\Resources\RestaurantOrderItems\RestaurantOrderItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantOrderItem extends CreateRecord
{
    protected static string $resource = RestaurantOrderItemResource::class;
}
