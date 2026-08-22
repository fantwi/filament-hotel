<?php

namespace App\Filament\Admin\Resources\ConferenceFacilities\Pages;

use App\Filament\Admin\Resources\ConferenceFacilities\ConferenceFacilityResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create conference facility.
 */
class CreateConferenceFacility extends CreateRecord
{
    protected static string $resource = ConferenceFacilityResource::class;
}
