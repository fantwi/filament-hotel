<?php

namespace App\Filament\Admin\Resources\Ingredients\Pages;

use App\Filament\Admin\Resources\Ingredients\IngredientResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create ingredient.
 */
class CreateIngredient extends CreateRecord
{
    protected static string $resource = IngredientResource::class;
}
