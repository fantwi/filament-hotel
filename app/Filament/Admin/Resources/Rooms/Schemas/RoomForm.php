<?php

namespace App\Filament\Admin\Resources\Rooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                Select::make('room_type_id')
                    ->relationship('roomType', 'name')
                    ->required()
                    ->searchable(),

                TextInput::make('room_number')
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'maintenance' => 'Maintenance',
                    ])
                    ->default('available')
                    ->required(),
            ]);
    }
}
