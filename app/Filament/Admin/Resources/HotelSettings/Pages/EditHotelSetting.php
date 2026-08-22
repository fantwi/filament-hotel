<?php

namespace App\Filament\Admin\Resources\HotelSettings\Pages;

use App\Filament\Admin\Resources\HotelSettings\HotelSettingResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit hotel setting.
 */
class EditHotelSetting extends EditRecord
{
    protected static string $resource = HotelSettingResource::class;
}
