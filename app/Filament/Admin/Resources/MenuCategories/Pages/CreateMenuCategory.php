<?php

namespace App\Filament\Admin\Resources\MenuCategories\Pages;

use App\Filament\Admin\Resources\MenuCategories\MenuCategoryResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create menu category.
 */
class CreateMenuCategory extends CreateRecord
{
    protected static string $resource = MenuCategoryResource::class;
}
