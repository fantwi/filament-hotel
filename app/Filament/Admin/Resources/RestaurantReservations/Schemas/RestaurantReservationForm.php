<?php

namespace App\Filament\Admin\Resources\RestaurantReservations\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Guest;

class RestaurantReservationForm
{
    public static function configure(
        Schema $schema,
    ): Schema {

        return $schema
            ->components([

                Section::make('Reservation')

                    ->schema([

                        Select::make('restaurant_id')

                            ->relationship(
                                'restaurant',
                                'name'
                            )

                            ->required(),

                        Select::make('restaurant_table_id')

                            ->relationship(
                                'table',
                                'table_number'
                            )

                            ->required(),

                        Select::make('guest_id')

                            ->relationship(
                                'guest',
                                'email'
                            )

                            ->searchable(),

                        TextInput::make('guest_name')
                            ->required(),

                        TextInput::make('guest_email')
                            ->email()
                            ->required(),

                        TextInput::make('guest_phone'),

                        DatePicker::make('reservation_date')
                            ->required(),

                        TimePicker::make('reservation_time')
                            ->seconds(false)
                            ->required(),

                        TextInput::make('number_of_guests')
                            ->numeric()
                            ->required(),

                        Textarea::make('special_requests')
                            ->rows(3),

                    ])->columns(2),

                Section::make('Reservation Status')

                    ->schema([

                        Select::make('status')

                            ->options([

                                'pending' => 'Pending',

                                'confirmed' => 'Confirmed',

                                'checked_in' => 'Checked In',

                                'completed' => 'Completed',

                                'cancelled' => 'Cancelled',

                                'no_show' => 'No Show',

                            ])

                            ->default('pending'),

                        Select::make('payment_status')

                            ->options([

                                'pending' => 'Pending',

                                'partial' => 'Partial',

                                'completed' => 'Completed',

                                'cancelled' => 'Cancelled',

                                'refunded' => 'Refunded',

                            ])

                            ->default('pending'),

                    ])->columns(2),

            ]);

    }
}