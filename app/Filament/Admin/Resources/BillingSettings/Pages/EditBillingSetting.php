<?php

namespace App\Filament\Admin\Resources\BillingSettings\Pages;

use App\Filament\Admin\Resources\BillingSettings\BillingSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditBillingSetting extends EditRecord
{
    protected static string $resource = BillingSettingResource::class;
}
