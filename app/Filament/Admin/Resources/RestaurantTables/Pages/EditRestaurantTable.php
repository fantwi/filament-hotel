<?php

namespace App\Filament\Admin\Resources\RestaurantTables\Pages;

use App\Filament\Admin\Resources\RestaurantTables\RestaurantTableResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit restaurant table.
 */
class EditRestaurantTable extends EditRecord
{
    protected static string $resource = RestaurantTableResource::class;

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
