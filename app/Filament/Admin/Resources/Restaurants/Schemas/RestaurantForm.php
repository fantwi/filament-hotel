<?php

namespace App\Filament\Admin\Resources\Restaurants\Schemas;

use Filament\Schemas\Schema;

class RestaurantForm
{
    // public static function configure(Schema $schema): Schema
    // {
    //     return $schema
    //         ->components([
    //             //
    //         ]);
    // }

    public static function schema(Schema $schema): Schema {

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
                            ->image()
                            ->directory('restaurants'),

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
