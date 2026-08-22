<?php

namespace App\Filament\Admin\Resources\BillingSettings\Pages;

use App\Filament\Admin\Resources\BillingSettings\BillingSettingResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create billing setting.
 */
class CreateBillingSetting extends CreateRecord
{
    protected static string $resource = BillingSettingResource::class;
}
