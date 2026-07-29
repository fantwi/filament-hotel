<?php

namespace App\Filament\Admin\Resources\Guests\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GuestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->email()
                    ->maxLength(50),

                TextInput::make('phone_number')
                    ->label('Phone Number')
                    ->tel()
                    ->required()
                    ->maxLength(20),

                TextInput::make('id_number')
                    ->label('ID Number')
                    ->required()
                    ->maxLength(20)
                    ->unique(),

                Select::make('department')
                    // ->default('Guest')
                    ->options(User::getGuestDepartment())
                    ->required(),
                // ->dehydrated()
                // ->native(false),

                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                    ->label(fn (string $operation): string => $operation === 'edit' ? 'New Password' : 'Password')
                    ->helperText('Leave blank to keep the current password.'),
            ]);
    }
}
