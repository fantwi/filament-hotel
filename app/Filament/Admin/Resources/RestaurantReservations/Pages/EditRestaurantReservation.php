<?php

namespace App\Filament\Admin\Resources\RestaurantReservations\Pages;

use App\Filament\Admin\Resources\RestaurantReservations\RestaurantReservationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit restaurant reservation.
 */
class EditRestaurantReservation extends EditRecord
{
    protected static string $resource = RestaurantReservationResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
