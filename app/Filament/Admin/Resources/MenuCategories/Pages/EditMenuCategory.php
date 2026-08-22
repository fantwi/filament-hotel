<?php

namespace App\Filament\Admin\Resources\MenuCategories\Pages;

use App\Filament\Admin\Resources\MenuCategories\MenuCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit menu category.
 */
class EditMenuCategory extends EditRecord
{
    protected static string $resource = MenuCategoryResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
