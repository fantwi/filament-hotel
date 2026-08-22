<?php

namespace App\Filament\Admin\Resources\ContactMessages\Pages;

use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create contact message.
 */
class CreateContactMessage extends CreateRecord
{
    protected static string $resource = ContactMessageResource::class;
}
