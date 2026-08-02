<?php

namespace App\Filament\Admin\Resources\CorporateOrganizations\Pages;

use App\Filament\Admin\Resources\CorporateOrganizations\CorporateOrganizationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCorporateOrganization extends EditRecord
{
    protected static string $resource = CorporateOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
