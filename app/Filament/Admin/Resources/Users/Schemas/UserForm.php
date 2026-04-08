<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required(),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required(),

            Forms\Components\TextInput::make('password')
                ->password()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn ($state): bool => filled($state))
                ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                ->label(fn (string $operation): string => $operation === 'edit' ? 'New Password' : 'Password')
                ->helperText('Leave blank to keep the current password.'),

            Forms\Components\Select::make('roles')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->options(function () {
                    $roles = \Spatie\Permission\Models\Role::pluck('name', 'name');

                    if (! auth()->user()->hasRole('super_admin')) {
                        $roles = $roles->except(['admin', 'super_admin']);
                    }

                    return $roles;
                }),

            Forms\Components\Select::make('role')
                ->label('Role')
                ->options([
                    'super_admin' => 'Super Admin',
                    'admin' => 'Admin',
                    'manager' => 'Manager',
                    'receptionist' => 'Receptionist',
                    'housekeeping' => 'Housekeeping',
                    'accountant' => 'Accountant',
                ])
                ->required(),

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

            Forms\Components\Select::make('shift')
                ->label('Work Shift')
                ->options([
                    'morning' => 'Morning Shift',
                    'evening' => 'Evening Shift',
                    'night' => 'Night Shift',
                    'off_duty' => 'Off Duty',
                ])
                ->default('off_duty')
                ->required(),
        ]);
    }
}
