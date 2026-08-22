<?php

namespace App\Filament\Admin\Resources\KitchenStockMovements\Pages;

use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list kitchen stock movements.
 */
class ListKitchenStockMovements extends ListRecords
{
    protected static string $resource = KitchenStockMovementResource::class;
}
