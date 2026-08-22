<?php

namespace App\Filament\Admin\Resources\Facilities\Pages;

use App\Filament\Admin\Resources\Facilities\FacilityResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create facility.
 */
class CreateFacility extends CreateRecord
{
    protected static string $resource = FacilityResource::class;
}
