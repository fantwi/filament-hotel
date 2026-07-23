<?php

namespace App\Filament\Admin\Resources\MenuCategories\Pages;

use App\Filament\Admin\Resources\MenuCategories\MenuCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMenuCategory extends CreateRecord
{
    protected static string $resource = MenuCategoryResource::class;
}
