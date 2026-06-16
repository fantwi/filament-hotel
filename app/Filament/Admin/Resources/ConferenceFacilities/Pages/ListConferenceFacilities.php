<?php

namespace App\Filament\Admin\Resources\ConferenceFacilities\Pages;

use App\Filament\Admin\Resources\ConferenceFacilities\ConferenceFacilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConferenceFacilities extends ListRecords
{
    protected static string $resource = ConferenceFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
