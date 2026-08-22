<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Schemas;

use Filament\Schemas\Schema;

/**
 * Configures Filament administration for activity log form.
 */
class ActivityLogForm
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
