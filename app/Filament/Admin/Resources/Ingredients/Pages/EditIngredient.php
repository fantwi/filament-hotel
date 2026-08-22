<?php

namespace App\Filament\Admin\Resources\Ingredients\Pages;

use App\Filament\Admin\Resources\Ingredients\IngredientResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit ingredient.
 */
class EditIngredient extends EditRecord
{
    protected static string $resource = IngredientResource::class;
}
