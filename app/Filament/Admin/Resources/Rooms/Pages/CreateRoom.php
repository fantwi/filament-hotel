<?php

namespace App\Filament\Admin\Resources\Rooms\Pages;

use App\Filament\Admin\Resources\Rooms\RoomResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create room.
 */
class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;
}
