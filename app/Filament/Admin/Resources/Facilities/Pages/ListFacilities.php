<?php

namespace App\Filament\Admin\Resources\Facilities\Pages;

use App\Filament\Admin\Resources\Facilities\FacilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list facilities.
 */
class ListFacilities extends ListRecords
{
    protected static string $resource = FacilityResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
