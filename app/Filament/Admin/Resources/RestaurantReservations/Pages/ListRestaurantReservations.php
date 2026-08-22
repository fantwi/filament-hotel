<?php

namespace App\Filament\Admin\Resources\RestaurantReservations\Pages;

use App\Filament\Admin\Resources\RestaurantReservations\RestaurantReservationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list restaurant reservations.
 */
class ListRestaurantReservations extends ListRecords
{
    protected static string $resource = RestaurantReservationResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
