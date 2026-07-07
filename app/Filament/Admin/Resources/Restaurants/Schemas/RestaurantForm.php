<?php

namespace App\Filament\Admin\Resources\Restaurants\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
//use Filament\Forms\Components\Section;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema {

        return $schema

            ->components([

                Section::make('Restaurant Information')

                    ->schema([

                        TextInput::make('name')
                            ->required(),

                        Textarea::make('description')
                            ->rows(5)
                            ->required(),

                        FileUpload::make('hero_image')
                            ->label('Hero Image')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants')
                            ->visibility('public'),

                        TextInput::make('phone'),

                        TextInput::make('email')
                            ->email(),

                        TextInput::make('address'),

                        TimePicker::make('opening_time')
                            ->seconds(false),

                        TimePicker::make('closing_time')
                            ->seconds(false),

                        TextInput::make('capacity')
                            ->numeric(),

                        TextInput::make('dress_code'),

                        TextInput::make('cuisine'),

                        Toggle::make('is_open'),

                    ])

                    ->columns(2),

            ]);

    }
}
