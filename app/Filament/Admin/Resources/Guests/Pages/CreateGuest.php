<?php

namespace App\Filament\Admin\Resources\Guests\Pages;

use App\Filament\Admin\Resources\Guests\GuestResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create guest.
 */
class CreateGuest extends CreateRecord
{
    protected static string $resource = GuestResource::class;
}
