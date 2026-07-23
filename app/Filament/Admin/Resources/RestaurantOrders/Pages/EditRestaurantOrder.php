<?php

namespace App\Filament\Admin\Resources\RestaurantOrders\Pages;

use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantOrder extends EditRecord
{
    protected static string $resource = RestaurantOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
