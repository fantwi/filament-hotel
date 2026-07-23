<?php

namespace App\Filament\Admin\Resources\RestaurantOrders\Pages;

use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantOrders extends ListRecords
{
    protected static string $resource = RestaurantOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
