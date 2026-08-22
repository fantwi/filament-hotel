<?php

namespace App\Filament\Admin\Resources\RestaurantReservations\Pages;

use App\Filament\Admin\Resources\RestaurantReservations\RestaurantReservationResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create restaurant reservation.
 */
class CreateRestaurantReservation extends CreateRecord
{
    protected static string $resource = RestaurantReservationResource::class;
}
