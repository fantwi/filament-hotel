<?php

namespace App\Filament\Admin\Resources\RestaurantTables\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantTableForm
{
    public static function configure(Schema $schema): Schema
    {

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

                        FileUpload::make('image')
                            ->label('Table Image')
                            ->image()
                            ->disk('public')
                            ->directory('restaurant-tables')
                            ->visibility('public')
                            ->imageEditor()
                            ->columnSpanFull(),

                        Toggle::make('qr_ordering_enabled')
                            ->label('Enable QR Ordering')
                            ->helperText('Guests can scan this table QR code and place orders.')
                            ->default(true),

                    ])

                    ->columns(2),

            ]);

    }
}
