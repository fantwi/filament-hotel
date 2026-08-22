<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use Filament\Schemas\Schema;

/**
 * Configures Filament administration for booking infolist.
 */
class BookingInfolist
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
