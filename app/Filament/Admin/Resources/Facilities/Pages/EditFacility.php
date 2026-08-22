<?php

namespace App\Filament\Admin\Resources\Facilities\Pages;

use App\Filament\Admin\Resources\Facilities\FacilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit facility.
 */
class EditFacility extends EditRecord
{
    protected static string $resource = FacilityResource::class;

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
