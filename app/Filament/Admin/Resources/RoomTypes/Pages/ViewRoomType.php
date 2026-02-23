<?php

namespace App\Filament\Admin\Resources\RoomTypes\Pages;

use App\Filament\Admin\Resources\RoomTypes\RoomTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRoomType extends ViewRecord
{
    protected static string $resource = RoomTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
