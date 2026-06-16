<?php

namespace App\Filament\Admin\Resources\RoomTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\CheckboxList;

class RoomTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('price_per_night')
                    ->required()
                    ->numeric(),
                FileUpload::make('image')
                    ->directory('room-types')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    // ->validationRules(['image', 'max:2048'])
                    ->imageEditor(),
                TextInput::make('capacity')
                    ->required()
                    ->numeric(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                CheckboxList::make('facilities')
                    ->relationship('facilities','name')
                    ->columns(2)
                    ->searchable(),
            ]);
    }
}
