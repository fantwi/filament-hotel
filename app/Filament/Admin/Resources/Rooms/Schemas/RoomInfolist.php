<?php

namespace App\Filament\Admin\Resources\Rooms\Schemas;

use Filament\Schemas\Schema;

/**
 * Configures Filament administration for room infolist.
 */
class RoomInfolist
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
