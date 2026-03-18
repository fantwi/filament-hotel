<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
                //
                Forms\Components\TextInput::make('name')
                    ->required(),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state)),

                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        $roles = \Spatie\Permission\Models\Role::pluck('name','name');

                        if (!auth()->user()->hasRole('super_admin')) {
                            $roles = $roles->except(['admin','super_admin']);
                        }

                        return $roles;
                    }),

                // staff status
                Forms\Components\Select::make('status')
                    ->label('Staff Status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'on_leave' => 'On Leave',
                        'suspended' => 'Suspended',
                    ])
                    ->default('online')
                    ->required(),
            ]);
    }
}
