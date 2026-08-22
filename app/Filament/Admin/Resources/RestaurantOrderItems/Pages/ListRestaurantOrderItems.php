<?php

namespace App\Filament\Admin\Resources\RestaurantOrderItems\Pages;

use App\Filament\Admin\Resources\RestaurantOrderItems\RestaurantOrderItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list restaurant order items.
 */
class ListRestaurantOrderItems extends ListRecords
{
    protected static string $resource = RestaurantOrderItemResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
