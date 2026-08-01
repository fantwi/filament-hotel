<?php

namespace App\Filament\Admin\Resources\Ingredients\Pages;

use App\Filament\Admin\Resources\Ingredients\IngredientResource;
use Filament\Resources\Pages\EditRecord;

class EditIngredient extends EditRecord
{
    protected static string $resource = IngredientResource::class;
}
