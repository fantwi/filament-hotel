<?php

namespace App\Filament\Admin\Resources\RestaurantOrderItems\Pages;

use App\Filament\Admin\Resources\RestaurantOrderItems\RestaurantOrderItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantOrderItems extends ListRecords
{
    protected static string $resource = RestaurantOrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
