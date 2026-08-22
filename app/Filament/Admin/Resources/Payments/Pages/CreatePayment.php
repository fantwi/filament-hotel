<?php

namespace App\Filament\Admin\Resources\Payments\Pages;

use App\Filament\Admin\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create payment.
 */
class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
}
