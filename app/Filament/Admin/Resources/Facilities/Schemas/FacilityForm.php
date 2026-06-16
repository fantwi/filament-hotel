<?php

namespace App\Filament\Admin\Resources\Facilities\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;


class FacilityForm
{
    public static function configure(Schema $schema): Schema
    {
        // return $schema
        //     ->components([
        //         //
        //     ]);

        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('icon')
                    ->helperText(
                        'Optional icon name'
                    ),
            ]);
    }
}
