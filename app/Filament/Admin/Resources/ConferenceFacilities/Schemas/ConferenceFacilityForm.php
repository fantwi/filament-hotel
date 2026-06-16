<?php

namespace App\Filament\Admin\Resources\ConferenceFacilities\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Forms\Components\TextInput;

class ConferenceFacilityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('name')
                    ->required(),

                TextInput::make('icon')
                    ->helperText('Optional icon name'),
            ]);
    }
}
