<?php

namespace App\Filament\Admin\Resources\ConferenceFacilities\Pages;

use App\Filament\Admin\Resources\ConferenceFacilities\ConferenceFacilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list conference facilities.
 */
class ListConferenceFacilities extends ListRecords
{
    protected static string $resource = ConferenceFacilityResource::class;

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
