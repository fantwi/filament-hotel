<?php

namespace App\Filament\Admin\Resources\RestaurantReservations\Pages;

use App\Filament\Admin\Resources\RestaurantReservations\RestaurantReservationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantReservation extends EditRecord
{
    protected static string $resource = RestaurantReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
