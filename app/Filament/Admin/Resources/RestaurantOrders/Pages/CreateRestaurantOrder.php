<?php

namespace App\Filament\Admin\Resources\RestaurantOrders\Pages;

use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantOrder extends CreateRecord
{
    protected static string $resource = RestaurantOrderResource::class;
}
