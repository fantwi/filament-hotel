<?php

namespace App\Filament\Admin\Resources\RestaurantReservations\Pages;

use App\Filament\Admin\Resources\RestaurantReservations\RestaurantReservationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantReservations extends ListRecords
{
    protected static string $resource = RestaurantReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
