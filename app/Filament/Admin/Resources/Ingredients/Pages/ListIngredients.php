<?php

namespace App\Filament\Admin\Resources\Ingredients\Pages;

use App\Filament\Admin\Resources\Ingredients\IngredientResource;
use Filament\Resources\Pages\ListRecords;

class ListIngredients extends ListRecords
{
    protected static string $resource = IngredientResource::class;
}
