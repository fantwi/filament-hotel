<?php

namespace App\Filament\Admin\Resources\Guests\Pages;

use App\Filament\Admin\Resources\Guests\GuestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list guests.
 */
class ListGuests extends ListRecords
{
    protected static string $resource = GuestResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
