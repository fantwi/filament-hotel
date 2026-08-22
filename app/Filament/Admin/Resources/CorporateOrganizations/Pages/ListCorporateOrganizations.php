<?php

namespace App\Filament\Admin\Resources\CorporateOrganizations\Pages;

use App\Filament\Admin\Resources\CorporateOrganizations\CorporateOrganizationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list corporate organizations.
 */
class ListCorporateOrganizations extends ListRecords
{
    protected static string $resource = CorporateOrganizationResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
