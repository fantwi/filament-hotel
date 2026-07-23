<?php

namespace App\Filament\Admin\Resources\RestaurantOrderItems\Pages;

use App\Filament\Admin\Resources\RestaurantOrderItems\RestaurantOrderItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantOrderItem extends EditRecord
{
    protected static string $resource = RestaurantOrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
