<?php

namespace App\Filament\Admin\Resources\Guests\Pages;

use App\Filament\Admin\Resources\Guests\GuestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuest extends CreateRecord
{
    protected static string $resource = GuestResource::class;
}
