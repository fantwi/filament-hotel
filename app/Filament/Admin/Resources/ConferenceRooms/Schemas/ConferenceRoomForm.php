<?php

namespace App\Filament\Admin\Resources\ConferenceRooms\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Schema;

class ConferenceRoomForm
{
    public static function configure(Schema $schema): Schema
    {
        // return $schema
        //     ->components([
        //         //
        //     ]);

        return $schema
            ->components([

                TextInput::make('name')
                    ->required(),

                Textarea::make('description'),

                TextInput::make('capacity')
                    ->numeric()
                    ->required(),

                TextInput::make('price_per_hour')
                    ->numeric()
                    ->prefix('GHS'),

                CheckboxList::make('facilities')
                    ->relationship('facilities', 'name')
                    ->columns(2)
                    ->searchable(),

                FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->disk('public')
                    ->directory('conference-rooms')
                    ->visibility('public'),

                FileUpload::make('gallery')
                    ->label('Conference Room Gallery')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->disk('public')
                    ->directory('conference-rooms/gallery')
                    ->visibility('public')
                    ->columnSpanFull(),

                Toggle::make('is_available'),

            ]);
    }
}
