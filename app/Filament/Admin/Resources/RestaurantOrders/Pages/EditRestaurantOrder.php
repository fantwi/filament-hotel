<?php

namespace App\Filament\Admin\Resources\RestaurantOrders\Pages;

use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit restaurant order.
 */
class EditRestaurantOrder extends EditRecord
{
    protected static string $resource = RestaurantOrderResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
