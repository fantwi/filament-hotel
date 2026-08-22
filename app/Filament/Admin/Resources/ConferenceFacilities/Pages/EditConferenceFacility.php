<?php

namespace App\Filament\Admin\Resources\ConferenceFacilities\Pages;

use App\Filament\Admin\Resources\ConferenceFacilities\ConferenceFacilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit conference facility.
 */
class EditConferenceFacility extends EditRecord
{
    protected static string $resource = ConferenceFacilityResource::class;

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
