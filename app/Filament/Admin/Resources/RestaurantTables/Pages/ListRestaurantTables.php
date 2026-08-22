<?php

namespace App\Filament\Admin\Resources\RestaurantTables\Pages;

use App\Filament\Admin\Resources\RestaurantTables\RestaurantTableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list restaurant tables.
 */
class ListRestaurantTables extends ListRecords
{
    protected static string $resource = RestaurantTableResource::class;

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
