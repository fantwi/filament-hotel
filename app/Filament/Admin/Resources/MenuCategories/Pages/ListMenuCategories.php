<?php

namespace App\Filament\Admin\Resources\MenuCategories\Pages;

use App\Filament\Admin\Resources\MenuCategories\MenuCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list menu categories.
 */
class ListMenuCategories extends ListRecords
{
    protected static string $resource = MenuCategoryResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
