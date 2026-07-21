<?php

namespace App\Filament\Admin\Resources\RestaurantReservations\Pages;

use App\Filament\Admin\Resources\RestaurantReservations\RestaurantReservationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantReservation extends CreateRecord
{
    protected static string $resource = RestaurantReservationResource::class;
}
