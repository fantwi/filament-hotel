<?php

namespace App\Filament\Admin\Resources\KitchenProductions\Pages;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit kitchen production.
 */
class EditKitchenProduction extends EditRecord
{
    protected static string $resource = KitchenProductionResource::class;
}
