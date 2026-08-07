<?php

namespace App\Filament\Admin\Resources\HotelSettings\Pages;

use App\Filament\Admin\Resources\HotelSettings\HotelSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHotelSettings extends ListRecords
{
    protected static string $resource = HotelSettingResource::class;

    protected function getHeaderActions(): array
    {
        return HotelSettingResource::canCreate() ? [CreateAction::make()] : [];
    }
}
