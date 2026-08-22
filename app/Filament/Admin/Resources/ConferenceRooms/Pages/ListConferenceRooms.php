<?php

namespace App\Filament\Admin\Resources\ConferenceRooms\Pages;

use App\Filament\Admin\Resources\ConferenceRooms\ConferenceRoomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list conference rooms.
 */
class ListConferenceRooms extends ListRecords
{
    protected static string $resource = ConferenceRoomResource::class;

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
