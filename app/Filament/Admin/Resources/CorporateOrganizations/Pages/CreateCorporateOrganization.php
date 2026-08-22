<?php

namespace App\Filament\Admin\Resources\CorporateOrganizations\Pages;

use App\Filament\Admin\Resources\CorporateOrganizations\CorporateOrganizationResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create corporate organization.
 */
class CreateCorporateOrganization extends CreateRecord
{
    protected static string $resource = CorporateOrganizationResource::class;
}
