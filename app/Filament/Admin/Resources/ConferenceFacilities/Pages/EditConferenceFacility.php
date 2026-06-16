<?php

namespace App\Filament\Admin\Resources\ConferenceFacilities\Pages;

use App\Filament\Admin\Resources\ConferenceFacilities\ConferenceFacilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConferenceFacility extends EditRecord
{
    protected static string $resource = ConferenceFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
