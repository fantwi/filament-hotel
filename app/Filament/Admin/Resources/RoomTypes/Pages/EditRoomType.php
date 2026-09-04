<?php

namespace App\Filament\Admin\Resources\RoomTypes\Pages;

use App\Filament\Admin\Resources\RoomTypes\RoomTypeResource;
use App\Models\RoomType;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit room type.
 */
class EditRoomType extends EditRecord
{
    protected static string $resource = RoomTypeResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->disabled(fn (RoomType $record): bool => $record->rooms()->exists())
                ->tooltip(fn (RoomType $record): ?string => $record->rooms()->exists()
                    ? 'This room type is assigned to rooms. Unpublish it instead to preserve booking history.'
                    : null),
        ];
    }
}
