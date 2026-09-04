<?php

namespace App\Filament\Admin\Resources\RoomTypes\Pages;

use App\Filament\Admin\Resources\RoomTypes\RoomTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list room types.
 */
class ListRoomTypes extends ListRecords
{
    protected static string $resource = RoomTypeResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => RoomTypeResource::canCreate()),
        ];
    }
}
