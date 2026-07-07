<?php

namespace App\Filament\Admin\Resources\RestaurantTables\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;

class RestaurantTableForm
{
    public static function configure(Schema $schema): Schema {

        return $schema

            ->components([

                Section::make('Restaurant Table')

                    ->schema([

                        Select::make('restaurant_id')

                            ->relationship(

                                'restaurant',

                                'name'

                            )

                            ->required(),

                        TextInput::make('table_number')

                            ->required()

                            ->maxLength(50),

                        TextInput::make('capacity')

                            ->numeric()

                            ->required(),

                        TextInput::make('location')

                            ->placeholder(
                                'Indoor'
                            ),

                        Select::make('status')

                            ->options([

                                'available' => 'Available',

                                'reserved' => 'Reserved',

                                'occupied' => 'Occupied',

                                'cleaning' => 'Cleaning',

                                'maintenance' => 'Maintenance',

                            ])

                            ->default('available')

                            ->required(),

                        Textarea::make('description')

                            ->rows(3),

                    ])

                    ->columns(2),

            ]);

    }
}
