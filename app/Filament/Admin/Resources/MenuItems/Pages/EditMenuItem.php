<?php

namespace App\Filament\Admin\Resources\MenuItems\Pages;

use App\Filament\Admin\Resources\MenuItems\MenuItemResource;
use App\Models\MenuItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit menu item.
 */
class EditMenuItem extends EditRecord
{
    protected static string $resource = MenuItemResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (MenuItem $record): bool => $record->kitchenProductions()->exists())
                ->tooltip(fn (MenuItem $record): ?string => $record->kitchenProductions()->exists()
                    ? 'This menu item has production history. Unpublish it instead to preserve the kitchen stock ledger.'
                    : null),
        ];
    }
}
