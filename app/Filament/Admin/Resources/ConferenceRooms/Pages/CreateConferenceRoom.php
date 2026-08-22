<?php

namespace App\Filament\Admin\Resources\ConferenceRooms\Pages;

use App\Filament\Admin\Resources\ConferenceRooms\ConferenceRoomResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create conference room.
 */
class CreateConferenceRoom extends CreateRecord
{
    protected static string $resource = ConferenceRoomResource::class;
}
