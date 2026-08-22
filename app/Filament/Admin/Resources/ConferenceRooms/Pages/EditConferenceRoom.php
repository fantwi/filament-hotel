<?php

namespace App\Filament\Admin\Resources\ConferenceRooms\Pages;

use App\Filament\Admin\Resources\ConferenceRooms\ConferenceRoomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit conference room.
 */
class EditConferenceRoom extends EditRecord
{
    protected static string $resource = ConferenceRoomResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
