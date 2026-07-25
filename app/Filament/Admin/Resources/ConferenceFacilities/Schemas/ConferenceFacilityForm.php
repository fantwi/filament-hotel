<?php

namespace App\Filament\Admin\Resources\ConferenceFacilities\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

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
                Toggle::make('is_published')
                    ->label('Published for guests')
                    ->helperText('Only you can see this facility in Filament until it is published.')
                    ->onIcon('heroicon-m-eye')
                    ->offIcon('heroicon-m-eye-slash')
                    ->default(false),
            ]);
    }
}
