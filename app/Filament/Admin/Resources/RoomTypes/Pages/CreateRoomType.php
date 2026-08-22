<?php

namespace App\Filament\Admin\Resources\RoomTypes\Pages;

use App\Filament\Admin\Resources\RoomTypes\RoomTypeResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create room type.
 */
class CreateRoomType extends CreateRecord
{
    protected static string $resource = RoomTypeResource::class;
}
