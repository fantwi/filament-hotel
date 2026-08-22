<?php

namespace App\Filament\Admin\Resources\MenuItems\Pages;

use App\Filament\Admin\Resources\MenuItems\MenuItemResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create menu item.
 */
class CreateMenuItem extends CreateRecord
{
    protected static string $resource = MenuItemResource::class;
}
