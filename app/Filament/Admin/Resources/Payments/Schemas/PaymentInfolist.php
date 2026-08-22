<?php

namespace App\Filament\Admin\Resources\Payments\Schemas;

use Filament\Schemas\Schema;

/**
 * Configures Filament administration for payment infolist.
 */
class PaymentInfolist
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
