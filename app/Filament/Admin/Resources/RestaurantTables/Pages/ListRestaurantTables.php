<?php

namespace App\Filament\Admin\Resources\RestaurantTables\Pages;

use App\Filament\Admin\Resources\RestaurantTables\RestaurantTableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantTables extends ListRecords
{
    protected static string $resource = RestaurantTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
