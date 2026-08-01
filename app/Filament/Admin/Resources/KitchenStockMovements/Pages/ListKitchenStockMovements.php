<?php

namespace App\Filament\Admin\Resources\KitchenStockMovements\Pages;

use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListKitchenStockMovements extends ListRecords
{
    protected static string $resource = KitchenStockMovementResource::class;
}
