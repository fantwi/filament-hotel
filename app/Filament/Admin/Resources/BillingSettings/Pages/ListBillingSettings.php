<?php

namespace App\Filament\Admin\Resources\BillingSettings\Pages;

use App\Filament\Admin\Resources\BillingSettings\BillingSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list billing settings.
 */
class ListBillingSettings extends ListRecords
{
    protected static string $resource = BillingSettingResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
