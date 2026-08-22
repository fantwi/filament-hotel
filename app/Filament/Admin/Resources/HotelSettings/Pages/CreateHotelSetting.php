<?php

namespace App\Filament\Admin\Resources\HotelSettings\Pages;

use App\Filament\Admin\Resources\HotelSettings\HotelSettingResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create hotel setting.
 */
class CreateHotelSetting extends CreateRecord
{
    protected static string $resource = HotelSettingResource::class;
}
