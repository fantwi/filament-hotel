<?php

namespace App\Filament\Admin\Resources\ConferenceRooms\Pages;

use App\Filament\Admin\Resources\ConferenceRooms\ConferenceRoomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConferenceRoom extends EditRecord
{
    protected static string $resource = ConferenceRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
