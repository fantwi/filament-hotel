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
                Forms\Components\Toggle::make('is_published')
                    ->label('Published for guests')
                    ->helperText('Only you can see this facility in Filament until it is published.')
                    ->onIcon('heroicon-m-eye')
                    ->offIcon('heroicon-m-eye-slash')
                    ->default(false),
            ]);
    }
}
