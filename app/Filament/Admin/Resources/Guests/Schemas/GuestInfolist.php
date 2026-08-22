<?php

namespace App\Filament\Admin\Resources\Guests\Schemas;

use Filament\Schemas\Schema;

/**
 * Configures Filament administration for guest infolist.
 */
class GuestInfolist
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
