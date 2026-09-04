<?php

namespace App\Filament\Admin\Resources\RoomTypes\Pages;

use App\Filament\Admin\Resources\RoomTypes\RoomTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Configures Filament administration for view room type.
 */
class ViewRoomType extends ViewRecord
{
    protected static string $resource = RoomTypeResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => RoomTypeResource::canEdit($this->getRecord())),
        ];
    }
}
