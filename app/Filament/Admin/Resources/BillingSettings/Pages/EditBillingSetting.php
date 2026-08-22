<?php

namespace App\Filament\Admin\Resources\BillingSettings\Pages;

use App\Filament\Admin\Resources\BillingSettings\BillingSettingResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit billing setting.
 */
class EditBillingSetting extends EditRecord
{
    protected static string $resource = BillingSettingResource::class;
}
