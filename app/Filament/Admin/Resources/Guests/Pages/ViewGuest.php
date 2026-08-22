<?php

namespace App\Filament\Admin\Resources\Guests\Pages;

use App\Filament\Admin\Resources\Guests\GuestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Configures Filament administration for view guest.
 */
class ViewGuest extends ViewRecord
{
    protected static string $resource = GuestResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
