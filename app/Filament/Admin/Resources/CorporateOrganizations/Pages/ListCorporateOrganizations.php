<?php

namespace App\Filament\Admin\Resources\CorporateOrganizations\Pages;

use App\Filament\Admin\Resources\CorporateOrganizations\CorporateOrganizationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCorporateOrganizations extends ListRecords
{
    protected static string $resource = CorporateOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
